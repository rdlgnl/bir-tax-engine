<?php

namespace App\Assistant\Services;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Providers\ProviderFactory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;

/**
 * The Ask page's conversation. It lives in the session, so it ends when the
 * app closes: the provider's history, sent back as it was with each new
 * question, and the turns as the page shows them.
 *
 * @phpstan-import-type Figure from Conversation
 * @phpstan-import-type Figure from TaxLookups as LookupFigure
 *
 * @phpstan-type Rule array{id: string, version: int, title: string, basis: string}
 * @phpstan-type PageTurn array{question: string, answer: string, error: bool, figures: list<Figure>, suggestions: list<array<string, mixed>>, feedback: 'helpful'|'wrong'|null, rules: list<Rule>}
 */
class Assistant
{
    private const SESSION_KEY = 'assistant';

    /** The figures shown beside an answer, at most. */
    private const MAX_FIGURES = 8;

    public function __construct(
        private ProviderFactory $providers,
        private TaxLookups $lookups,
        private Session $session,
    ) {}

    public function conversation(): Conversation
    {
        return Conversation::fromSession($this->session->get(self::SESSION_KEY));
    }

    /**
     * The turns as the page shows them, with the rules each answer cites.
     *
     * @return list<PageTurn>
     */
    public function turns(): array
    {
        return array_map(fn (array $turn) => [...$turn, 'rules' => $turn['error'] ? [] : $this->rulesCited($turn['answer'])], $this->conversation()->turns);
    }

    public function ask(string $question): void
    {
        $settings = AssistantSettings::current();

        if (! $settings->ready()) {
            throw new AssistantUnavailable('Turn the assistant on and add your API key first.');
        }

        $conversation = $this->conversation();

        // Another provider or model can't continue this one's history.
        if ($conversation->with !== $settings->conversation()) {
            $conversation = new Conversation($settings->conversation());
        }

        $dated = 'Today is '.CarbonImmutable::now()->format('l, F j, Y').".\n\n".$question;

        try {
            $turn = $this->providers->for($settings)->ask($conversation->history, $dated, $this->lookups);
            $conversation->history = $turn->history;
            $conversation->turns[] = ['question' => $question, 'answer' => $turn->text, 'error' => false, 'figures' => $this->figures($turn->figures), 'suggestions' => $turn->suggestions, 'feedback' => null];
        } catch (AssistantUnavailable $e) {
            // The question isn't kept in the history: asking again starts clean.
            $conversation->turns[] = ['question' => $question, 'answer' => $e->getMessage(), 'error' => true, 'figures' => [], 'suggestions' => [], 'feedback' => null];
        }

        $this->session->put(self::SESSION_KEY, $conversation->toSession());
    }

    /**
     * @param  'helpful'|'wrong'  $feedback
     */
    public function feedback(int $turn, string $feedback): void
    {
        $conversation = $this->conversation();

        if (isset($conversation->turns[$turn])) {
            $conversation->turns[$turn]['feedback'] = $feedback;
            $this->session->put(self::SESSION_KEY, $conversation->toSession());
        }
    }

    /**
     * A suggestion the taxpayer approved (and the app's form took) or set aside.
     *
     * @param  'done'|'dismissed'  $status
     */
    public function suggestion(int $turn, int $index, string $status): void
    {
        $conversation = $this->conversation();

        if (isset($conversation->turns[$turn]['suggestions'][$index])) {
            $conversation->turns[$turn]['suggestions'][$index]['status'] = $status;
            $this->session->put(self::SESSION_KEY, $conversation->toSession());
        }
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * The rule-pack rules an answer cites by id.
     *
     * @return list<Rule>
     */
    private function rulesCited(string $text): array
    {
        $cited = [];

        foreach ((array) config('tax.rules') as $id => $rule) {
            if (preg_match('/(?<![A-Z0-9-])'.preg_quote((string) $id, '/').'(?![A-Z0-9-])/', $text) === 1) {
                $cited[] = ['id' => (string) $id, 'version' => (int) $rule['version'], 'title' => (string) $rule['title'], 'basis' => (string) $rule['basis']];
            }
        }

        return $cited;
    }

    /**
     * Each figure once, at its latest value.
     *
     * @param  list<LookupFigure>  $figures
     * @return list<Figure>
     */
    private function figures(array $figures): array
    {
        $latest = [];

        foreach ($figures as [$label, $value]) {
            unset($latest[$label]);
            $latest[$label] = $value;
        }

        $rows = [];

        foreach ($latest as $label => $value) {
            $rows[] = ['label' => (string) $label, 'value' => $value];
        }

        return array_slice($rows, -self::MAX_FIGURES);
    }
}
