<?php

namespace App\Assistant\Providers;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Prompt;
use App\Assistant\Services\TaxLookups;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Smalot\PdfParser\Parser;

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
        $suggestions = [];

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            $choice = $this->send($history, $lookups);
            $message = (array) ($choice['message'] ?? []);
            $calls = array_values((array) ($message['tool_calls'] ?? []));
            $history[] = ['role' => 'assistant', 'content' => $message['content'] ?? null, ...($calls !== [] ? ['tool_calls' => $calls] : [])];
            $text = trim((string) ($message['content'] ?? ''));

            if (($choice['finish_reason'] ?? null) === 'content_filter') {
                return new Turn("I can't help with that one. Try asking it another way.", $history, $figures, $suggestions);
            }

            if ($calls === []) {
                $cutOff = ($choice['finish_reason'] ?? null) === 'length' ? "\n\n(The answer was cut off.)" : '';

                return new Turn(($text !== '' ? $text : "I don't have an answer to that.").$cutOff, $history, $figures, $suggestions);
            }

            foreach ($calls as $call) {
                $arguments = json_decode((string) ($call['function']['arguments'] ?? ''), true);

                try {
                    $lookup = $lookups->run((string) ($call['function']['name'] ?? ''), is_array($arguments) ? $arguments : []);
                    $content = $lookup['content'];
                    array_push($figures, ...$lookup['figures']);

                    if (isset($lookup['suggestion'])) {
                        $suggestions[] = $lookup['suggestion'];
                    }
                } catch (InvalidArgumentException $e) {
                    $content = 'Error: '.$e->getMessage();
                }

                $history[] = ['role' => 'tool', 'tool_call_id' => $call['id'] ?? '', 'content' => $content];
            }
        }

        return new Turn("I couldn't finish that answer. Try a narrower question.", $history, $figures, $suggestions);
    }

    public function read(Document $document, string $instructions, array $schema): array
    {
        if ($document->isPdf()) {
            // Not every compatible server takes a PDF: send its text, read here.
            $text = $this->pdfText($document->contents);

            if ($text === '') {
                throw new AssistantUnavailable("This PDF is a scan with no text in it, and {$this->host()} would need it as a picture. Add a photo or screenshot of the letter instead.");
            }

            $content = $instructions."\n\nThe letter's text:\n\n".$text;
        } else {
            $content = [
                ['type' => 'text', 'text' => $instructions],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$document->mediaType};base64,{$document->base64()}"]],
            ];
        }

        $body = ['model' => $this->model, 'messages' => [['role' => 'user', 'content' => $content]]];
        $response = $this->post([...$body, 'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'answer', 'strict' => true, 'schema' => $schema]]]);

        // A server without structured outputs is asked again, for JSON in words.
        if ($response->status() === 400) {
            $response = $this->post($body);
        }

        $choice = $this->choice($response);

        return JsonObject::decode((string) ($choice['message']['content'] ?? ''))
            ?? throw new AssistantUnavailable("{$this->host()} didn't send back the details in a form the app could read. Try again, or fill them in yourself.");
    }

    /**
     * @param  list<array<string, mixed>>  $history
     * @return array<string, mixed> the first choice
     */
    private function send(array $history, TaxLookups $lookups): array
    {
        return $this->choice($this->post([
            'model' => $this->model,
            'messages' => [['role' => 'system', 'content' => Prompt::system()], ...$history],
            'tools' => array_map(fn (array $tool) => ['type' => 'function', 'function' => [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => $tool['schema'],
            ]], $lookups->definitions()),
        ]));
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(array $body): Response
    {
        $request = Http::acceptJson()->timeout(120)->connectTimeout(15);

        if ($this->key !== null && $this->key !== '') {
            $request = $request->withToken($this->key);
        }

        try {
            return $request->post($this->baseUrl.'/chat/completions', $body);
        } catch (ConnectionException) {
            throw new AssistantUnavailable("Couldn't reach {$this->host()}. Check the address and your internet connection, and try again.");
        }
    }

    /**
     * @return array<string, mixed> the reply's first choice
     */
    private function choice(Response $response): array
    {
        $host = $this->host();
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

    private function host(): string
    {
        return parse_url($this->baseUrl, PHP_URL_HOST) ?: $this->baseUrl;
    }

    private function pdfText(string $contents): string
    {
        try {
            return trim((new Parser)->parseContent($contents)->getText());
        } catch (Exception) {
            return '';
        }
    }
}
