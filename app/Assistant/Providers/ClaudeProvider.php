<?php

namespace App\Assistant\Providers;

use Anthropic\Beta\Messages\BetaAssistantMessageParam;
use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Beta\Messages\BetaTool;
use Anthropic\Beta\Messages\BetaUserMessageParam;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Prompt;
use App\Assistant\Services\TaxLookups;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;

/**
 * Claude, through Anthropic's SDK: the question, then each lookup it asks
 * for, until it answers.
 *
 * The history goes back exactly as Claude returned it, thinking blocks
 * included: the API checks that a conversation's earlier turns, system
 * prompt and tools are unchanged, and caches them.
 *
 * @phpstan-import-type Figure from TaxLookups
 */
class ClaudeProvider implements Provider
{
    /** Lookups one answer may make before it gives up. */
    private const MAX_ROUNDS = 8;

    /** Models that take the API's own fallback when they decline to answer. */
    private const FALLBACK_MODELS = ['claude-opus-5-5', 'claude-sonnet-5-5'];

    private Client $client;

    public function __construct(string $key, private string $model, ?ClientInterface $transporter = null)
    {
        $options = ['timeout' => 120.0, 'maxRetries' => 2];

        if ($transporter !== null) {
            $options['transporter'] = $transporter;
        }

        $this->client = new Client(apiKey: $key, requestOptions: $options, openTelemetry: false);
    }

    public function ask(array $history, string $question, TaxLookups $lookups): Turn
    {
        $history[] = ['role' => 'user', 'content' => $question];
        /** @var list<Figure> $figures */
        $figures = [];
        $suggestions = [];

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            $message = $this->send($history, $lookups);
            /** @var list<array<string, mixed>> $content */
            $content = json_decode((string) json_encode($message->content), true);
            $history[] = ['role' => 'assistant', 'content' => $content];
            $text = $this->text($content);

            if ($message->stopReason === 'refusal') {
                return new Turn("I can't help with that one. Try asking it another way.", $history, $figures, $suggestions);
            }

            if ($message->stopReason !== 'tool_use') {
                $cutOff = $message->stopReason === 'max_tokens' ? "\n\n(The answer was cut off.)" : '';

                return new Turn(($text !== '' ? $text : "I don't have an answer to that.").$cutOff, $history, $figures, $suggestions);
            }

            $results = [];

            foreach ($content as $block) {
                if (($block['type'] ?? null) !== 'tool_use') {
                    continue;
                }

                try {
                    $lookup = $lookups->run((string) $block['name'], (array) ($block['input'] ?? []));
                    $results[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => $lookup['content']];
                    array_push($figures, ...$lookup['figures']);

                    if (isset($lookup['suggestion'])) {
                        $suggestions[] = $lookup['suggestion'];
                    }
                } catch (InvalidArgumentException $e) {
                    $results[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => $e->getMessage(), 'is_error' => true];
                }
            }

            $history[] = ['role' => 'user', 'content' => $results];
        }

        return new Turn("I couldn't finish that answer. Try a narrower question.", $history, $figures, $suggestions);
    }

    /**
     * @param  list<array<string, mixed>>  $history  thinking blocks are dropped from it once, when the API says the history no longer matches them
     */
    private function send(array &$history, TaxLookups $lookups): BetaMessage
    {
        try {
            return $this->request($history, $lookups);
        } catch (BadRequestException $e) {
            if (! $this->hasThinking($history)) {
                throw new AssistantUnavailable('Anthropic turned the request down: '.$e->getMessage());
            }

            // A conversation whose thinking no longer checks out is answered
            // without it, once; the next turns build on that.
            $history = $this->withoutThinking($history);

            try {
                return $this->request($history, $lookups);
            } catch (BadRequestException $e) {
                throw new AssistantUnavailable('Anthropic turned the request down: '.$e->getMessage());
            }
        }
    }

    public function read(Document $document, string $instructions, array $schema): array
    {
        $message = ['role' => 'user', 'content' => [
            $document->isPdf()
                ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $document->base64()]]
                : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $document->mediaType, 'data' => $document->base64()]],
            ['type' => 'text', 'text' => $instructions],
        ]];

        try {
            $reply = $this->request([$message], null, $schema);
        } catch (BadRequestException $e) {
            throw new AssistantUnavailable("Anthropic couldn't take this file: ".$e->getMessage());
        }

        if ($reply->stopReason === 'refusal') {
            throw new AssistantUnavailable('The assistant declined to read this document.');
        }

        /** @var list<array<string, mixed>> $content */
        $content = json_decode((string) json_encode($reply->content), true);
        $answer = $reply->stopReason === 'max_tokens' ? null : JsonObject::decode($this->text($content));

        return $answer ?? throw new AssistantUnavailable("The assistant couldn't finish reading this document. Try again, or fill in the details yourself.");
    }

    /**
     * A conversation turn with the lookups as tools, or a one-off answer as
     * JSON the schema describes.
     *
     * @param  list<array<string, mixed>>  $history
     * @param  array<string, mixed>|null  $schema
     */
    private function request(array $history, ?TaxLookups $lookups, ?array $schema = null): BetaMessage
    {
        $fallback = in_array($this->model, self::FALLBACK_MODELS, true);

        try {
            return $this->client->beta->messages->create(
                maxTokens: 16000,
                messages: array_map(fn (array $message) => $message['role'] === 'assistant'
                    ? BetaAssistantMessageParam::fromArray($message)
                    : BetaUserMessageParam::fromArray($message), $history),
                model: $this->model,
                cacheControl: $lookups !== null ? ['type' => 'ephemeral'] : null,
                fallbacks: $fallback ? 'default' : null,
                outputConfig: $schema === null ? ['effort' => 'medium'] : ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
                system: $lookups !== null ? [['type' => 'text', 'text' => Prompt::system()]] : null,
                thinking: ['type' => 'adaptive'],
                tools: $lookups === null ? null : array_map(fn (array $tool) => BetaTool::fromArray([
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'input_schema' => $tool['schema'],
                ]), $lookups->definitions()),
                betas: $fallback ? ['server-side-fallback-2026-07-01'] : null,
            );
        } catch (AuthenticationException|PermissionDeniedException) {
            throw new AssistantUnavailable("Anthropic didn't accept your API key. Check it under Profile & settings, Your data.");
        } catch (NotFoundException) {
            throw new AssistantUnavailable("Your API key can't use {$this->model}. Choose another model under Profile & settings, Your data.");
        } catch (RateLimitException) {
            throw new AssistantUnavailable('Anthropic is limiting requests on your key. Try again in a minute.');
        } catch (BadRequestException $e) {
            throw $e;
        } catch (APIStatusException $e) {
            throw new AssistantUnavailable("Anthropic couldn't answer just now (error {$e->status}). Try again in a moment.");
        } catch (APIConnectionException) {
            throw new AssistantUnavailable("Couldn't reach Anthropic. Check your internet connection and try again.");
        }
    }

    /**
     * @param  list<array<string, mixed>>  $content
     */
    private function text(array $content): string
    {
        $parts = array_map(fn (array $block) => (string) $block['text'], array_filter($content, fn (array $block) => ($block['type'] ?? null) === 'text'));

        return trim(implode("\n\n", $parts));
    }

    /**
     * @param  list<array<string, mixed>>  $history
     */
    private function hasThinking(array $history): bool
    {
        foreach ($history as $message) {
            foreach (is_array($message['content']) ? $message['content'] : [] as $block) {
                if (in_array($block['type'] ?? null, ['thinking', 'redacted_thinking'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $history
     * @return list<array<string, mixed>>
     */
    private function withoutThinking(array $history): array
    {
        return array_map(fn (array $message) => is_array($message['content'])
            ? [...$message, 'content' => array_values(array_filter($message['content'], fn (array $block) => ! in_array($block['type'] ?? null, ['thinking', 'redacted_thinking'], true)))]
            : $message, $history);
    }
}
