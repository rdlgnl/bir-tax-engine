<?php

namespace App\Assistant\Providers;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Prompt;
use App\Assistant\Services\TaxLookups;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * OpenAI, or any server with the same chat completions API (OpenRouter,
 * Groq, a local Ollama), through its function calling: the question, then
 * each lookup it asks for, until it answers.
 *
 * @phpstan-import-type Figure from TaxLookups
 */
class OpenAiProvider implements Provider
{
    /** Lookups one answer may make before it gives up. */
    private const MAX_ROUNDS = 8;

    public function __construct(private ?string $key, private string $model, private string $baseUrl) {}

    public function ask(array $history, string $question, TaxLookups $lookups): Turn
    {
        $history[] = ['role' => 'user', 'content' => $question];
        /** @var list<Figure> $figures */
        $figures = [];

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            $choice = $this->send($history, $lookups);
            $message = (array) ($choice['message'] ?? []);
            $calls = array_values((array) ($message['tool_calls'] ?? []));
            $history[] = ['role' => 'assistant', 'content' => $message['content'] ?? null, ...($calls !== [] ? ['tool_calls' => $calls] : [])];
            $text = trim((string) ($message['content'] ?? ''));

            if (($choice['finish_reason'] ?? null) === 'content_filter') {
                return new Turn("I can't help with that one. Try asking it another way.", $history, $figures);
            }

            if ($calls === []) {
                $cutOff = ($choice['finish_reason'] ?? null) === 'length' ? "\n\n(The answer was cut off.)" : '';

                return new Turn(($text !== '' ? $text : "I don't have an answer to that.").$cutOff, $history, $figures);
            }

            foreach ($calls as $call) {
                $arguments = json_decode((string) ($call['function']['arguments'] ?? ''), true);

                try {
                    $lookup = $lookups->run((string) ($call['function']['name'] ?? ''), is_array($arguments) ? $arguments : []);
                    $content = $lookup['content'];
                    array_push($figures, ...$lookup['figures']);
                } catch (InvalidArgumentException $e) {
                    $content = 'Error: '.$e->getMessage();
                }

                $history[] = ['role' => 'tool', 'tool_call_id' => $call['id'] ?? '', 'content' => $content];
            }
        }

        return new Turn("I couldn't finish that answer. Try a narrower question.", $history, $figures);
    }

    /**
     * @param  list<array<string, mixed>>  $history
     * @return array<string, mixed> the first choice
     */
    private function send(array $history, TaxLookups $lookups): array
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST) ?: $this->baseUrl;
        $request = Http::acceptJson()->timeout(120)->connectTimeout(15);

        if ($this->key !== null && $this->key !== '') {
            $request = $request->withToken($this->key);
        }

        try {
            $response = $request->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [['role' => 'system', 'content' => Prompt::system()], ...$history],
                'tools' => array_map(fn (array $tool) => ['type' => 'function', 'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['schema'],
                ]], $lookups->definitions()),
            ]);
        } catch (ConnectionException) {
            throw new AssistantUnavailable("Couldn't reach {$host}. Check the address and your internet connection, and try again.");
        }

        $choice = $response->json('choices.0');

        return match (true) {
            in_array($response->status(), [401, 403], true) => throw new AssistantUnavailable("{$host} didn't accept your API key. Check it under Profile & settings, Your data."),
            $response->status() === 404 => throw new AssistantUnavailable("{$host} has no model called {$this->model}, or the address is wrong. Check both under Profile & settings, Your data."),
            $response->status() === 429 => throw new AssistantUnavailable("{$host} is limiting requests on your key. Try again in a minute."),
            $response->failed() => throw new AssistantUnavailable("{$host} couldn't answer just now (error {$response->status()}). Try again in a moment."),
            ! is_array($choice) => throw new AssistantUnavailable("{$host} sent back something the app couldn't read."),
            default => $choice,
        };
    }
}
