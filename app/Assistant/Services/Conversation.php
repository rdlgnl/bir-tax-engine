<?php

namespace App\Assistant\Services;

/**
 * The conversation on the Ask page: who it's with (provider and model),
 * the provider's history exactly as it was, and the questions and answers
 * as the page shows them. Kept in the session, as JSON.
 *
 * @phpstan-type Figure array{label: string, value: string}
 * @phpstan-type Exchange array{question: string, answer: string, error: bool, figures: list<Figure>, feedback: 'helpful'|'wrong'|null}
 */
final class Conversation
{
    /**
     * @param  list<array<string, mixed>>  $history
     * @param  list<Exchange>  $turns
     */
    public function __construct(
        public ?string $with = null,
        public array $history = [],
        public array $turns = [],
    ) {}

    /**
     * Read back from the session; anything that isn't a conversation starts a new one.
     */
    public static function fromSession(mixed $saved): self
    {
        if (! is_array($saved) || ! is_string($saved['with'] ?? null) || ! is_array($saved['history'] ?? null) || ! is_array($saved['turns'] ?? null)) {
            return new self;
        }

        $history = [];

        foreach ($saved['history'] as $message) {
            if (! is_array($message)) {
                return new self;
            }

            $history[] = self::withStringKeys($message);
        }

        $turns = [];

        foreach ($saved['turns'] as $turn) {
            if (! is_array($turn) || ! is_string($turn['question'] ?? null) || ! is_string($turn['answer'] ?? null) || ! is_bool($turn['error'] ?? null)) {
                return new self;
            }

            $figures = [];

            foreach (is_array($turn['figures'] ?? null) ? $turn['figures'] : [] as $figure) {
                if (is_array($figure) && is_string($figure['label'] ?? null) && is_string($figure['value'] ?? null)) {
                    $figures[] = ['label' => $figure['label'], 'value' => $figure['value']];
                }
            }

            $feedback = $turn['feedback'] ?? null;
            $turns[] = [
                'question' => $turn['question'],
                'answer' => $turn['answer'],
                'error' => $turn['error'],
                'figures' => $figures,
                'feedback' => $feedback === 'helpful' || $feedback === 'wrong' ? $feedback : null,
            ];
        }

        return new self($saved['with'], $history, $turns);
    }

    /**
     * @return array{with: string|null, history: list<array<string, mixed>>, turns: list<Exchange>}
     */
    public function toSession(): array
    {
        return ['with' => $this->with, 'history' => $this->history, 'turns' => $this->turns];
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private static function withStringKeys(array $values): array
    {
        $kept = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $kept[$key] = $value;
            }
        }

        return $kept;
    }
}
