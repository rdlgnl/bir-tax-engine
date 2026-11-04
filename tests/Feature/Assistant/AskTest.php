<?php

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Providers\Document;
use App\Assistant\Providers\Provider;
use App\Assistant\Providers\ProviderFactory;
use App\Assistant\Providers\Turn;
use App\Assistant\Services\AssistantSettings;
use App\Assistant\Services\TaxLookups;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A provider that answers from a script and remembers what it was asked.
 */
class ScriptedProvider implements Provider
{
    /** @var list<array{history: list<array<string, mixed>>, question: string}> */
    public array $asked = [];

    /** @param list<Turn|AssistantUnavailable|callable(array<int, mixed>, string): Turn> $script */
    public function __construct(private array $script) {}

    public function ask(array $history, string $question, TaxLookups $lookups): Turn
    {
        $this->asked[] = ['history' => $history, 'question' => $question];
        $next = array_shift($this->script);

        return match (true) {
            $next instanceof AssistantUnavailable => throw $next,
            $next instanceof Turn => $next,
            default => $next($history, $question),
        };
    }

    public function read(Document $document, string $instructions, array $schema): array
    {
        throw new AssistantUnavailable('Not reading documents in this test.');
    }
}

function scripted(array $script): ScriptedProvider
{
    $provider = new ScriptedProvider($script);
    app()->instance(ProviderFactory::class, new class($provider) extends ProviderFactory
    {
        public function __construct(private ScriptedProvider $provider) {}

        public function for(AssistantSettings $settings): Provider
        {
            return $this->provider;
        }
    });

    return $provider;
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create();
    $this->put('/data/assistant', ['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'key' => 'sk-ant-secret-123']);
});

test('an answer shows with the rules it cites and the figures it used', function () {
    $provider = scripted([
        new Turn(
            'Because you chose the 8% option on your Q1 return (IT-8PCT-PURE), there is no separate percentage tax.',
            [['role' => 'user', 'content' => 'q1'], ['role' => 'assistant', 'content' => [['type' => 'text', 'text' => 'a1']]]],
            [['Q3 1701Q payable', '₱14,000.00'], ['2026 sales so far', '₱800,000.00'], ['Q3 1701Q payable', '₱14,000.00']],
        ),
    ]);

    $this->post('/ask', ['question' => 'Why don’t I pay percentage tax?'])->assertSessionHasNoErrors()->assertRedirect();

    expect($provider->asked[0]['question'])->toBe("Today is Wednesday, November 4, 2026.\n\nWhy don’t I pay percentage tax?")
        ->and($provider->asked[0]['history'])->toBe([]);

    $this->get('/ask')->assertInertia(fn (Assert $page) => $page
        ->component('ask')
        ->where('ready', true)
        ->where('provider', 'Claude Opus 5.5')
        ->has('turns', 1)
        ->where('turns.0.question', 'Why don’t I pay percentage tax?')
        ->where('turns.0.error', false)
        ->where('turns.0.rules.0.id', 'IT-8PCT-PURE')
        ->where('turns.0.rules.0.version', 2)
        ->has('turns.0.rules', 1)
        // Each figure once.
        ->where('turns.0.figures', [
            ['label' => '2026 sales so far', 'value' => '₱800,000.00'],
            ['label' => 'Q3 1701Q payable', 'value' => '₱14,000.00'],
        ]));
});

test('the next question continues the conversation, exactly as the provider left it', function () {
    $history = [['role' => 'user', 'content' => 'q1'], ['role' => 'assistant', 'content' => [['type' => 'thinking', 'thinking' => '', 'signature' => 'sig-1'], ['type' => 'text', 'text' => 'a1']]]];
    $provider = scripted([new Turn('First answer.', $history, []), new Turn('Second answer.', [...$history, ['role' => 'user', 'content' => 'q2']], [])]);

    $this->post('/ask', ['question' => 'First?']);
    $this->post('/ask', ['question' => 'Second?']);

    expect($provider->asked[1]['history'])->toBe($history);
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page->has('turns', 2)->where('turns.1.answer', 'Second answer.'));
});

test('choosing another model starts a new conversation', function () {
    $provider = scripted([new Turn('One.', [['role' => 'user', 'content' => 'q1']], []), new Turn('Two.', [], [])]);

    $this->post('/ask', ['question' => 'First?']);
    $this->put('/data/assistant', ['enabled' => true, 'provider' => 'claude', 'model' => 'claude-sonnet-5-5']);
    $this->post('/ask', ['question' => 'Second?']);

    expect($provider->asked[1]['history'])->toBe([]);
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page->has('turns', 1)->where('provider', 'Claude Sonnet 5.5'));
});

test('a provider that fails shows why, and the question is not kept in the history', function () {
    $provider = scripted([new AssistantUnavailable("Anthropic didn't accept your API key."), new Turn('Fine now.', [], [])]);

    $this->post('/ask', ['question' => 'First?'])->assertSessionHasNoErrors();
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page
        ->where('turns.0.error', true)
        ->where('turns.0.answer', "Anthropic didn't accept your API key.")
        ->where('turns.0.rules', []));

    $this->post('/ask', ['question' => 'Again?']);
    expect($provider->asked[1]['history'])->toBe([]);
});

test('feedback is kept with the answer, and a new conversation clears it all', function () {
    scripted([new Turn('An answer.', [], [])]);
    $this->post('/ask', ['question' => 'First?']);

    $this->post('/ask/feedback', ['turn' => 0, 'feedback' => 'wrong'])->assertRedirect();
    $this->post('/ask/feedback', ['turn' => 0, 'feedback' => 'meh'])->assertSessionHasErrors('feedback');
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page->where('turns.0.feedback', 'wrong'));

    $this->delete('/ask')->assertRedirect();
    $this->get('/ask')->assertInertia(fn (Assert $page) => $page->where('turns', []));
});

test('a question must be asked, and not too long', function () {
    scripted([]);

    $this->post('/ask', ['question' => ''])->assertSessionHasErrors('question');
    $this->post('/ask', ['question' => str_repeat('a', 2001)])->assertSessionHasErrors('question');
});
