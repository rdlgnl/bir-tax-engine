<?php

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Prompt;
use App\Assistant\Providers\ClaudeProvider;
use App\Assistant\Services\TaxLookups;
use App\Profiles\Models\TaxProfile;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create(['first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'full_name' => 'Maria Dela Cruz', 'tin' => '123-456-789-000']);
    Transaction::factory()->on('2026-08-10')->create(['counterparty' => 'Lumen Digital', 'amount' => 400_000]);
    $this->sent = [];
});

/**
 * A Claude reply as the API sends it.
 *
 * @param  list<array<string, mixed>>  $content
 */
function claudeReply(array $content, string $stopReason): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
        'id' => 'msg_'.uniqid(), 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
        'content' => $content, 'stop_reason' => $stopReason, 'stop_sequence' => null, 'stop_details' => null,
        'usage' => ['input_tokens' => 100, 'output_tokens' => 20],
    ]));
}

/**
 * The provider, answering from the given replies, each request it sends kept in $this->sent.
 *
 * @param  list<Response>  $replies
 */
function claude(object $test, array $replies, string $model = 'claude-opus-5-5'): ClaudeProvider
{
    $stack = HandlerStack::create(new MockHandler($replies));
    $stack->push(Middleware::history($test->sent));

    return new ClaudeProvider('sk-ant-test', $model, new Guzzle(['handler' => $stack]));
}

/** @return array<string, mixed> */
function sentBody(object $test, int $index): array
{
    return json_decode((string) $test->sent[$index]['request']->getBody(), true);
}

test('it looks figures up, answers, and keeps the whole conversation as Claude sent it', function () {
    $provider = claude($this, [
        claudeReply([
            ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig-1'],
            ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'tax_year', 'input' => ['year' => 2026]],
        ], 'tool_use'),
        claudeReply([['type' => 'text', 'text' => 'Your Q3 tax is ₱12,000.00 under IT-8PCT-PURE.']], 'end_turn'),
    ]);

    $turn = $provider->ask([], "Today is Wednesday, November 4, 2026.\n\nWhat's my Q3 tax?", app(TaxLookups::class));

    expect($turn->text)->toBe('Your Q3 tax is ₱12,000.00 under IT-8PCT-PURE.')
        ->and($turn->figures)->toContain(['2026 sales so far', '₱400,000.00'])
        ->and(array_column($turn->history, 'role'))->toBe(['user', 'assistant', 'user', 'assistant'])
        ->and($turn->history[1]['content'][0])->toMatchArray(['type' => 'thinking', 'signature' => 'sig-1'])
        ->and($this->sent)->toHaveCount(2);

    $first = sentBody($this, 0);
    $second = sentBody($this, 1);

    // The second request replays the first turn verbatim, thinking included,
    // with the same system prompt and tools.
    expect($second['messages'][1]['content'][0])->toMatchArray(['type' => 'thinking', 'signature' => 'sig-1'])
        ->and($second['messages'][2]['content'][0])->toMatchArray(['type' => 'tool_result', 'tool_use_id' => 'toolu_1'])
        ->and(json_decode($second['messages'][2]['content'][0]['content'], true))->toMatchArray(['year' => 2026, 'sales_so_far' => 400000])
        ->and($second['system'])->toBe($first['system'])
        ->and($second['system'][0]['text'])->toBe(Prompt::system())
        ->and($second['tools'])->toBe($first['tools'])
        ->and(array_column($first['tools'], 'name'))->toBe(['tax_year', 'quarter_steps', 'deadlines', 'compare_methods', 'letters', 'rules'])
        ->and($first)->toMatchArray(['model' => 'claude-opus-5-5', 'max_tokens' => 16000, 'fallbacks' => 'default', 'thinking' => ['type' => 'adaptive'], 'output_config' => ['effort' => 'medium'], 'cache_control' => ['type' => 'ephemeral']])
        ->and($this->sent[0]['request']->getHeaderLine('anthropic-beta'))->toBe('server-side-fallback-2026-07-01')
        ->and($this->sent[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer sk-ant-test');

    // Never the taxpayer's name or TIN, nor their client's name.
    foreach ([0, 1] as $index) {
        expect((string) $this->sent[$index]['request']->getBody())
            ->not->toContain('Maria')->not->toContain('Dela Cruz')->not->toContain('123-456-789')->not->toContain('Lumen');
    }
});

test('a lookup it gets wrong comes back as an error for it to correct', function () {
    $provider = claude($this, [
        claudeReply([['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'tax_year', 'input' => ['year' => 1999]]], 'tool_use'),
        claudeReply([['type' => 'text', 'text' => 'The app has no rules for 1999.']], 'end_turn'),
    ]);

    $provider->ask([], 'What about 1999?', app(TaxLookups::class));

    expect(sentBody($this, 1)['messages'][2]['content'][0])->toMatchArray(['type' => 'tool_result', 'tool_use_id' => 'toolu_1', 'is_error' => true]);
});

test('Claude Haiku has no fallback model to send', function () {
    claude($this, [claudeReply([['type' => 'text', 'text' => 'Hi.']], 'end_turn')], 'claude-haiku-5-5')->ask([], 'Hi?', app(TaxLookups::class));

    expect(sentBody($this, 0))->not->toHaveKey('fallbacks')
        ->and($this->sent[0]['request']->hasHeader('anthropic-beta'))->toBeFalse();
});

test('a refusal or a cut-off answer says so', function () {
    $refused = claude($this, [claudeReply([], 'refusal')])->ask([], 'Something odd', app(TaxLookups::class));
    $cut = claude($this, [claudeReply([['type' => 'text', 'text' => 'The first part']], 'max_tokens')])->ask([], 'A long one', app(TaxLookups::class));

    expect($refused->text)->toBe("I can't help with that one. Try asking it another way.")
        ->and($cut->text)->toBe("The first part\n\n(The answer was cut off.)");
});

test('a key Anthropic refuses, or no connection, is explained', function () {
    $error = fn (int $status, string $type) => new Response($status, ['Content-Type' => 'application/json'], (string) json_encode(['type' => 'error', 'error' => ['type' => $type, 'message' => 'nope']]));

    expect(fn () => claude($this, [$error(401, 'authentication_error')])->ask([], 'Hi?', app(TaxLookups::class)))
        ->toThrow(AssistantUnavailable::class, "Anthropic didn't accept your API key.")
        ->and(fn () => claude($this, [$error(404, 'not_found_error')])->ask([], 'Hi?', app(TaxLookups::class)))
        ->toThrow(AssistantUnavailable::class, "Your API key can't use claude-opus-5-5.");
});

test('history whose thinking no longer checks out is answered once without it', function () {
    $history = [
        ['role' => 'user', 'content' => 'First?'],
        ['role' => 'assistant', 'content' => [['type' => 'thinking', 'thinking' => '', 'signature' => 'stale'], ['type' => 'text', 'text' => 'First answer.']]],
    ];
    $mismatch = new Response(400, ['Content-Type' => 'application/json'], (string) json_encode(['type' => 'error', 'error' => ['type' => 'invalid_request_error', 'message' => 'messages.1.content.0: Invalid `signature` in `thinking` block.']]));

    $turn = claude($this, [$mismatch, claudeReply([['type' => 'text', 'text' => 'Second answer.']], 'end_turn')])
        ->ask($history, 'Second?', app(TaxLookups::class));

    expect($turn->text)->toBe('Second answer.')
        ->and(sentBody($this, 0)['messages'][1]['content'][0]['type'])->toBe('thinking')
        ->and(sentBody($this, 1)['messages'][1]['content'])->toBe([['type' => 'text', 'text' => 'First answer.']])
        ->and($turn->history[1]['content'])->toBe([['type' => 'text', 'text' => 'First answer.']]);
});
