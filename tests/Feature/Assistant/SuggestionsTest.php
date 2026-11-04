<?php

use App\Assistant\Providers\ClaudeProvider;
use App\Assistant\Providers\Provider;
use App\Assistant\Providers\ProviderFactory;
use App\Assistant\Providers\Turn;
use App\Assistant\Services\AssistantSettings;
use App\Assistant\Services\TaxLookups;
use App\Profiles\Models\TaxProfile;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create();
});

/** @return array<string, mixed> */
function suggest(string $name, array $input): array
{
    return app(TaxLookups::class)->run($name, $input)['suggestion'];
}

test('an email draft is shown, never sent', function () {
    $result = app(TaxLookups::class)->run('draft_email', ['to' => 'ap@lumen.ph', 'subject' => 'Your 2307 for Q3', 'body' => "Hi,\n\nCould you send the 2307 for Q3?\n\n[Your name]"]);

    expect($result['suggestion'])->toBe(['kind' => 'email', 'to' => 'ap@lumen.ph', 'subject' => 'Your 2307 for Q3', 'body' => "Hi,\n\nCould you send the 2307 for Q3?\n\n[Your name]", 'status' => 'open'])
        ->and($result['content'])->toContain('Nothing has changed yet')
        ->and($result['figures'])->toBe([])
        ->and(fn () => suggest('draft_email', ['to' => 'not an address', 'subject' => 'x', 'body' => 'y']))->toThrow(InvalidArgumentException::class, "isn't an email address");
});

test('a suggested transaction is checked, and the app’s own form takes it as it is', function () {
    $payment = suggest('suggest_transaction', ['type' => 'payment', 'occurred_on' => '2026-11-03', 'counterparty' => ' Lumen Digital ', 'amount' => 20000, 'tax_withheld' => 1000]);

    expect($payment)->toBe(['kind' => 'transaction', 'transaction' => [
        'type' => 'payment', 'occurred_on' => '2026-11-03', 'counterparty' => 'Lumen Digital', 'amount' => 20000.0, 'tax_withheld' => 1000.0,
    ], 'status' => 'open']);

    // Approving posts exactly this to the transactions form.
    $this->post('/transactions', ['tax_withheld' => 0, ...$payment['transaction']])->assertSessionHasNoErrors();
    expect(Transaction::query()->sole())->counterparty->toBe('Lumen Digital')->and((float) Transaction::query()->sole()->tax_withheld)->toBe(1000.0);

    $expense = suggest('suggest_transaction', ['type' => 'expense', 'occurred_on' => '2026-11-01', 'counterparty' => 'PLDT', 'amount' => 1899.5, 'tax_withheld' => 50, 'has_valid_invoice' => true]);
    expect($expense['transaction'])->toMatchArray(['category' => 'other', 'tax_withheld' => 0.0, 'has_valid_invoice' => true]);
    $this->post('/transactions', $expense['transaction'])->assertSessionHasNoErrors();
});

test('a transaction that can’t be is turned back to the model', function (array $input, string $message) {
    expect(fn () => suggest('suggest_transaction', [...['type' => 'payment', 'occurred_on' => '2026-11-03', 'counterparty' => 'Lumen', 'amount' => 1000], ...$input]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'a future date' => [['occurred_on' => '2026-11-05'], 'The date must be from 2018-01-01 to today.'],
    'not a date' => [['occurred_on' => 'yesterday'], 'occurred_on must be a date'],
    'withheld over the amount' => [['tax_withheld' => 1000], 'any tax withheld less than it'],
    'no amount' => [['amount' => '1000'], 'The amount must be a number'],
    'an invoice without its number' => [['type' => 'invoice'], 'invoice_number is needed.'],
    'an unknown type' => [['type' => 'refund'], 'The type must be payment, invoice or expense.'],
]);

test('a profile change points to the answer’s page', function () {
    expect(suggest('suggest_profile_change', ['step' => 'expenses', 'change' => 'Deduction method: optional standard to itemized', 'why' => 'Your expenses are over 40% of sales.']))
        ->toBe(['kind' => 'profile', 'step' => 'expenses', 'step_label' => 'Expenses (graduated only)', 'change' => 'Deduction method: optional standard to itemized', 'why' => 'Your expenses are over 40% of sales.', 'status' => 'open'])
        ->and(fn () => suggest('suggest_profile_change', ['step' => 'about', 'change' => 'Name', 'why' => 'Typo']))->toThrow(InvalidArgumentException::class, 'The step must be one of');
});

test('Claude’s suggestions come back with its answer, and the page keeps where each stands', function () {
    $sent = [];
    $reply = fn (array $content, string $stop) => new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
        'id' => 'msg_'.uniqid(), 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
        'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null, 'stop_details' => null, 'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
    ]));
    $stack = HandlerStack::create(new MockHandler([
        $reply([['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'suggest_transaction', 'input' => ['type' => 'payment', 'occurred_on' => '2026-11-03', 'counterparty' => 'Lumen Digital', 'amount' => 20000]]], 'tool_use'),
        $reply([['type' => 'text', 'text' => "I've put it below for you to add."]], 'end_turn'),
    ]));
    $stack->push(Middleware::history($sent));
    $claude = new ClaudeProvider('sk-ant-test', 'claude-opus-5-5', new Guzzle(['handler' => $stack]));

    $turn = $claude->ask([], 'Lumen paid me ₱20,000 yesterday.', app(TaxLookups::class));

    expect($turn)->toBeInstanceOf(Turn::class)
        ->and($turn->suggestions)->toHaveCount(1)
        ->and($turn->suggestions[0]['transaction']['counterparty'])->toBe('Lumen Digital')
        ->and(json_decode((string) $sent[1]['request']->getBody(), true)['messages'][2]['content'][0]['content'])->toContain('Nothing has changed yet');

    // Through the page: shown open, then marked done once added.
    AssistantSettings::current()->save(['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'base_url' => null], 'sk-ant-test');
    app()->instance(ProviderFactory::class, new class($turn) extends ProviderFactory
    {
        public function __construct(private Turn $turn) {}

        public function for(AssistantSettings $settings): Provider
        {
            return new class($this->turn) extends ClaudeProvider
            {
                public function __construct(private Turn $turn) {}

                public function ask(array $history, string $question, TaxLookups $lookups): Turn
                {
                    return $this->turn;
                }
            };
        }
    });

    $this->post('/ask', ['question' => 'Lumen paid me ₱20,000 yesterday.']);
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page
        ->where('turns.0.suggestions.0.kind', 'transaction')
        ->where('turns.0.suggestions.0.status', 'open'));

    $this->post('/ask/suggestions', ['turn' => 0, 'index' => 0, 'status' => 'done'])->assertRedirect();
    $this->post('/ask/suggestions', ['turn' => 0, 'index' => 0, 'status' => 'applied'])->assertSessionHasErrors('status');
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page->where('turns.0.suggestions.0.status', 'done'));
});
