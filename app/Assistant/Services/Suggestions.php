<?php

namespace App\Assistant\Services;

use App\Profiles\Enums\SetupStep;
use App\Tax\Services\TaxRules;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * What the assistant can suggest, never do: an email for the taxpayer to
 * send, a transaction for them to add, an answer in their profile for them
 * to change. Each becomes a preview on the Ask page; nothing changes until
 * they approve it there, through the app's own forms and checks.
 *
 * @phpstan-type Suggestion array<string, mixed>
 */
class Suggestions
{
    /** The setup steps an answer can be changed on: not who they are, which the forms take as typed. */
    private const STEPS = ['income', 'work', 'job', 'certificate', 'estimate', 'method', 'expenses', 'withholding'];

    public function __construct(private TaxRules $rules) {}

    /**
     * @return list<array{name: string, description: string, schema: array<string, mixed>}>
     */
    public function definitions(): array
    {
        $string = ['type' => 'string'];

        return [
            [
                'name' => 'draft_email',
                'description' => 'Puts an email in front of the taxpayer to copy or open in their email app, such as asking a client for a missing 2307 or replying to a BIR office. Nothing is sent: they read it and send it themselves.',
                'schema' => ['type' => 'object', 'properties' => [
                    'to' => ['type' => 'string', 'description' => 'The address, only if they gave it.'],
                    'subject' => $string,
                    'body' => ['type' => 'string', 'description' => 'The email, signed with a placeholder like [Your name]; no TIN unless they asked for it.'],
                ], 'required' => ['subject', 'body']],
            ],
            [
                'name' => 'suggest_transaction',
                'description' => 'Puts a transaction in front of the taxpayer to add, when they tell you about money they received, an invoice they issued or an expense they paid. Nothing is recorded until they approve it.',
                'schema' => ['type' => 'object', 'properties' => [
                    'type' => ['type' => 'string', 'enum' => array_column(TransactionType::cases(), 'value'), 'description' => 'payment: money received; invoice: an invoice issued; expense: money paid out.'],
                    'occurred_on' => ['type' => 'string', 'description' => 'The date, YYYY-MM-DD.'],
                    'counterparty' => ['type' => 'string', 'description' => 'The client or supplier.'],
                    'amount' => ['type' => 'number', 'description' => 'The full amount in pesos, before any tax withheld.'],
                    'tax_withheld' => ['type' => 'number', 'description' => 'Tax the client withheld, in pesos (0 if none).'],
                    'category' => ['type' => 'string', 'enum' => array_column(ExpenseCategory::cases(), 'value'), 'description' => 'For an expense.'],
                    'has_valid_invoice' => ['type' => 'boolean', 'description' => "For an expense: whether there's an official receipt or invoice in the taxpayer's name."],
                    'invoice_number' => ['type' => 'string', 'description' => 'For an invoice.'],
                    'description' => $string,
                ], 'required' => ['type', 'occurred_on', 'counterparty', 'amount']],
            ],
            [
                'name' => 'suggest_profile_change',
                'description' => 'Points the taxpayer to an answer in their tax profile that looks wrong, with the change to make. They change it themselves on that page.',
                'schema' => ['type' => 'object', 'properties' => [
                    'step' => ['type' => 'string', 'enum' => self::STEPS, 'description' => implode('; ', array_map(fn (string $step) => "{$step}: ".SetupStep::from($step)->label(), self::STEPS))],
                    'change' => ['type' => 'string', 'description' => 'What to change, from what to what, in a few words.'],
                    'why' => ['type' => 'string', 'description' => 'Why, in a sentence.'],
                ], 'required' => ['step', 'change', 'why']],
            ],
        ];
    }

    public function handles(string $name): bool
    {
        return in_array($name, ['draft_email', 'suggest_transaction', 'suggest_profile_change'], true);
    }

    /**
     * The suggestion, checked, and what the model is told.
     *
     * @param  array<string, mixed>  $input
     * @return array{content: string, suggestion: Suggestion}
     *
     * @throws InvalidArgumentException for a suggestion that can't be shown; the model is told so
     */
    public function run(string $name, array $input): array
    {
        $suggestion = match ($name) {
            'draft_email' => $this->email($input),
            'suggest_transaction' => $this->transaction($input),
            'suggest_profile_change' => $this->profileChange($input),
            default => throw new InvalidArgumentException("There's no suggestion called {$name}."),
        };

        return [
            'content' => 'Shown to the taxpayer to approve. Nothing has changed yet: tell them to look it over and approve it if it is right.',
            'suggestion' => [...$suggestion, 'status' => 'open'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return Suggestion
     */
    private function email(array $input): array
    {
        $to = $this->text($input, 'to', 200, required: false);

        if ($to !== null && filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("{$to} isn't an email address; leave it out.");
        }

        return ['kind' => 'email', 'to' => $to, 'subject' => $this->text($input, 'subject', 200), 'body' => $this->text($input, 'body', 5000)];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return Suggestion
     */
    private function transaction(array $input): array
    {
        $type = TransactionType::tryFrom((string) ($input['type'] ?? '')) ?? throw new InvalidArgumentException('The type must be payment, invoice or expense.');
        $date = is_string($input['occurred_on'] ?? null) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $input['occurred_on'], $parts) === 1 && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            ? $input['occurred_on'] : throw new InvalidArgumentException('occurred_on must be a date, YYYY-MM-DD.');

        if ($date > CarbonImmutable::now()->toDateString() || $date < $this->rules->firstDate()->toDateString()) {
            throw new InvalidArgumentException("The date must be from {$this->rules->firstDate()->toDateString()} to today.");
        }

        $amount = $this->money($input, 'amount') ?? throw new InvalidArgumentException('The amount must be a number of pesos over zero.');
        $withheld = $this->money($input, 'tax_withheld') ?? 0.0;

        if ($amount <= 0 || $withheld >= $amount) {
            throw new InvalidArgumentException('The amount must be over zero, and any tax withheld less than it.');
        }

        $category = $type === TransactionType::Expense
            ? (ExpenseCategory::tryFrom((string) ($input['category'] ?? '')) ?? ExpenseCategory::Other)->value
            : null;

        return ['kind' => 'transaction', 'transaction' => array_filter([
            'type' => $type->value,
            'occurred_on' => $date,
            'counterparty' => $this->text($input, 'counterparty', 120),
            'amount' => $amount,
            'tax_withheld' => $type === TransactionType::Expense ? 0.0 : $withheld,
            'category' => $category,
            'has_valid_invoice' => $type === TransactionType::Expense ? ($input['has_valid_invoice'] ?? false) === true : null,
            'invoice_number' => $type === TransactionType::Invoice ? $this->text($input, 'invoice_number', 40) : null,
            'description' => $this->text($input, 'description', 255, required: false),
        ], fn (mixed $value) => $value !== null)];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return Suggestion
     */
    private function profileChange(array $input): array
    {
        $step = in_array($input['step'] ?? null, self::STEPS, true) ? (string) $input['step'] : throw new InvalidArgumentException('The step must be one of: '.implode(', ', self::STEPS).'.');

        return [
            'kind' => 'profile',
            'step' => $step,
            'step_label' => SetupStep::from($step)->label(),
            'change' => $this->text($input, 'change', 200),
            'why' => $this->text($input, 'why', 500),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return ($required is true ? string : string|null)
     */
    private function text(array $input, string $key, int $max, bool $required = true): ?string
    {
        $value = is_string($input[$key] ?? null) ? trim($input[$key]) : '';

        if ($value === '') {
            return $required ? throw new InvalidArgumentException("{$key} is needed.") : null;
        }

        return mb_strlen($value) > $max ? throw new InvalidArgumentException("{$key} must be at most {$max} characters.") : $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function money(array $input, string $key): ?float
    {
        $value = $input[$key] ?? null;

        return is_int($value) || is_float($value) ? round((float) $value, 2) : null;
    }
}
