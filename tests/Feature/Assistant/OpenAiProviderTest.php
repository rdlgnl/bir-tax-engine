<?php

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Prompt;
use App\Assistant\Providers\OpenAiProvider;
use App\Assistant\Services\TaxLookups;
use App\Profiles\Models\TaxProfile;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create(['tin' => '123-456-789-000']);
    Transaction::factory()->on('2026-08-10')->create(['counterparty' => 'Lumen Digital', 'amount' => 400_000]);
});

test('it looks figures up through function calls and answers', function () {
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push(['choices' => [['finish_reason' => 'tool_calls', 'message' => ['role' => 'assistant', 'content' => null, 'tool_calls' => [
            ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'tax_year', 'arguments' => '{"year":2026}']],
        ]]]]])
        ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'Your Q3 tax is ₱12,000.00.']]]]),
    ]);

    $turn = (new OpenAiProvider('sk-test', 'a-model', 'https://api.openai.com/v1'))->ask([], "What's my Q3 tax?", app(TaxLookups::class));

    expect($turn->text)->toBe('Your Q3 tax is ₱12,000.00.')
        ->and($turn->figures)->toContain(['2026 sales so far', '₱400,000.00'])
        ->and(array_column($turn->history, 'role'))->toBe(['user', 'assistant', 'tool', 'assistant']);

    $requests = Http::recorded();
    expect($requests)->toHaveCount(2);
    [$first] = $requests[0];
    [$second] = $requests[1];

    expect($first->url())->toBe('https://api.openai.com/v1/chat/completions')
        ->and($first->header('Authorization'))->toBe(['Bearer sk-test'])
        ->and($first['model'])->toBe('a-model')
        ->and($first['messages'][0])->toBe(['role' => 'system', 'content' => Prompt::system()])
        ->and($first['tools'][0])->toMatchArray(['type' => 'function'])
        ->and($first['tools'][0]['function']['name'])->toBe('tax_year')
        ->and($second['messages'][3])->toMatchArray(['role' => 'tool', 'tool_call_id' => 'call_1'])
        ->and(json_decode($second['messages'][3]['content'], true))->toMatchArray(['sales_so_far' => 400000])
        ->and($second->body())->not->toContain('123-456-789')->not->toContain('Lumen');
});

test('a local server without a key gets no Authorization header', function () {
    Http::fake(['localhost:11434/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'Hello.']]]])]);

    (new OpenAiProvider(null, 'llama3.2', 'http://localhost:11434/v1'))->ask([], 'Hi?', app(TaxLookups::class));

    Http::assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
});

test('a refused key, a wrong model or address, and a busy server are explained', function (int $status, string $message) {
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'nope']], $status)]);

    expect(fn () => (new OpenAiProvider('sk-test', 'a-model', 'https://api.openai.com/v1'))->ask([], 'Hi?', app(TaxLookups::class)))
        ->toThrow(AssistantUnavailable::class, $message);
})->with([
    'refused key' => [401, "api.openai.com didn't accept your API key."],
    'no such model' => [404, 'api.openai.com has no model called a-model, or the address is wrong.'],
    'rate limit' => [429, 'api.openai.com is limiting requests on your key.'],
    'server error' => [500, "api.openai.com couldn't answer just now (error 500)."],
]);
