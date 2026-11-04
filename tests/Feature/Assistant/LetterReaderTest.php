<?php

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Providers\ClaudeProvider;
use App\Assistant\Providers\Document;
use App\Assistant\Providers\OpenAiProvider;
use App\Assistant\Providers\ProviderFactory;
use App\Assistant\Services\AssistantSettings;
use App\Assistant\Services\LetterReader;
use App\Profiles\Models\TaxProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create();
    $this->sent = [];
});

/**
 * Turns the assistant on with Claude, answering from the given replies.
 *
 * @param  list<Response>  $replies
 */
function claudeReads(object $test, array $replies): void
{
    AssistantSettings::current()->save(['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'base_url' => null], 'sk-ant-test');
    $stack = HandlerStack::create(new MockHandler($replies));
    $stack->push(Middleware::history($test->sent));
    $claude = new ClaudeProvider('sk-ant-test', 'claude-opus-5-5', new Guzzle(['handler' => $stack]));

    app()->instance(ProviderFactory::class, new class($claude) extends ProviderFactory
    {
        public function __construct(private ClaudeProvider $claude) {}

        public function for(AssistantSettings $settings): ClaudeProvider
        {
            return $this->claude;
        }
    });
}

/** Claude's structured answer, as the API sends it. */
function claudeAnswer(array $answer, string $stopReason = 'end_turn'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
        'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
        'content' => [['type' => 'text', 'text' => json_encode($answer)]],
        'stop_reason' => $stopReason, 'stop_sequence' => null, 'stop_details' => null,
        'usage' => ['input_tokens' => 1000, 'output_tokens' => 100],
    ]));
}

function letterPdfBytes(): string
{
    return Pdf::loadHTML('<p>Revenue District Office 028. Reminder: your 1701Q for the third quarter of 2026 has not been filed. Please file within ten days.</p>')->output();
}

function letterPdf(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('letter.pdf', letterPdfBytes());
}

function photo(int $width, int $height): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagepng($image);

    return UploadedFile::fake()->createWithContent('letter.png', (string) ob_get_clean());
}

$answer = [
    'is_bir_letter' => true, 'kind' => 'unfiled', 'dated_on' => '2026-11-02', 'office' => ' Revenue District Office 028 ',
    'form' => '1701Q', 'year' => 2026, 'quarter' => null, 'month' => 8, 'reply_by' => '2026-11-12',
    'summary' => '  It says your Q3 2026 1701Q was not filed and asks you to file it by November 12.  ',
];

test('Claude reads a PDF letter into the letter form’s fields', function () use ($answer) {
    claudeReads($this, [claudeAnswer($answer)]);

    $reading = app(LetterReader::class)->read(letterPdf());

    expect($reading)->toBe([
        'is_bir_letter' => true, 'kind' => 'unfiled', 'dated_on' => '2026-11-02', 'office' => 'Revenue District Office 028',
        // A month without a quarter gives the quarter.
        'form' => '1701Q', 'year' => 2026, 'quarter' => 3, 'month' => 8, 'reply_by' => '2026-11-12',
        'summary' => 'It says your Q3 2026 1701Q was not filed and asks you to file it by November 12.',
    ]);

    $body = json_decode((string) $this->sent[0]['request']->getBody(), true);
    $content = $body['messages'][0]['content'];

    // The letter and the instructions only: no system prompt, lookups or history.
    expect($content[0])->toMatchArray(['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $content[0]['source']['data']]])
        ->and(base64_decode($content[0]['source']['data']))->toStartWith('%PDF')
        ->and($content[1]['text'])->toContain('unfiled: Reminder of a return not filed')->toContain('Today is 2026-11-04')
        ->and($body['output_config']['format'])->toMatchArray(['type' => 'json_schema'])
        ->and($body['output_config']['format']['schema']['required'])->toContain('kind', 'summary')
        ->and($body)->not->toHaveKey('tools')->not->toHaveKey('system')->not->toHaveKey('cache_control');
});

test('what the letter form wouldn’t accept is left blank', function () use ($answer) {
    claudeReads($this, [claudeAnswer([
        ...$answer, 'kind' => 'love_letter', 'dated_on' => '2026-12-25', 'form' => '9999', 'year' => 1990,
        'quarter' => 7, 'month' => 13, 'reply_by' => '2026-02-30', 'office' => '   ',
    ])]);

    expect(app(LetterReader::class)->read(letterPdf()))->toMatchArray([
        'kind' => 'other', 'dated_on' => null, 'form' => null, 'year' => null,
        'quarter' => null, 'month' => null, 'reply_by' => null, 'office' => null,
    ]);
});

test('a large photo is made smaller before it goes; a small one goes as it is', function () use ($answer) {
    claudeReads($this, [claudeAnswer($answer), claudeAnswer($answer)]);

    app(LetterReader::class)->read(photo(3000, 1500));
    app(LetterReader::class)->read(photo(800, 600));

    $large = json_decode((string) $this->sent[0]['request']->getBody(), true)['messages'][0]['content'][0];
    $small = json_decode((string) $this->sent[1]['request']->getBody(), true)['messages'][0]['content'][0];
    [$width, $height] = getimagesizefromstring(base64_decode($large['source']['data']));

    expect($large['type'])->toBe('image')
        ->and($large['source']['media_type'])->toBe('image/jpeg')
        ->and([$width, $height])->toBe([2000, 1000])
        ->and($small['source']['media_type'])->toBe('image/png');
});

test('a refusal, or the assistant being off, is explained', function () use ($answer) {
    claudeReads($this, [claudeAnswer($answer, 'refusal')]);
    expect(fn () => app(LetterReader::class)->read(letterPdf()))->toThrow(AssistantUnavailable::class, 'The assistant declined to read this document.');

    AssistantSettings::current()->save(['enabled' => false, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'base_url' => null], null);
    expect(fn () => app(LetterReader::class)->read(letterPdf()))->toThrow(AssistantUnavailable::class, 'Turn the assistant on');
});

test('an OpenAI-compatible provider gets a photo as an image, and is asked again without structured output if it can’t do it', function () use ($answer) {
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(['error' => ['message' => 'response_format not supported']], 400)
        ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'Here it is: '.json_encode($answer).' Hope that helps.']]]]),
    ]);

    $reading = (new OpenAiProvider('sk-test', 'a-model', 'https://api.openai.com/v1'))
        ->read(new Document('image/png', 'png-bytes'), 'Read this.', app(LetterReader::class)->schema());

    expect($reading['kind'])->toBe('unfiled');

    $requests = Http::recorded();
    [$first] = $requests[0];
    [$second] = $requests[1];

    expect($first['response_format']['type'])->toBe('json_schema')
        ->and($first['messages'][0]['content'][1])->toBe(['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,'.base64_encode('png-bytes')]])
        ->and($second->data())->not->toHaveKey('response_format');
});

test('an OpenAI-compatible provider gets a PDF’s text, read here, and a scan without text is turned back', function () use ($answer) {
    Http::fake(['api.openai.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode($answer)]]]])]);
    $provider = new OpenAiProvider('sk-test', 'a-model', 'https://api.openai.com/v1');

    $provider->read(new Document('application/pdf', letterPdfBytes()), 'Read this.', []);

    Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'], "The letter's text:")
        && str_contains($request['messages'][0]['content'], 'has not been filed')
        && ! str_contains($request->body(), '%PDF'));

    $scan = Pdf::loadHTML('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==">')->output();
    expect(fn () => $provider->read(new Document('application/pdf', $scan), 'Read this.', []))
        ->toThrow(AssistantUnavailable::class, 'This PDF is a scan with no text in it');
});
