<?php

namespace App\Tax\Commands;

use App\Tax\Services\RuleReview;
use Illuminate\Console\Command;

/**
 * For the tax professional reviewing the rule pack, and whoever records
 * their sign-off: each part and each cited rule, and who signed it off.
 * --strict fails while anything is left, for a release check.
 */
class RuleStatus extends Command
{
    protected $signature = 'rules:status {--strict : Fail while any part or rule isn\'t signed off}';

    protected $description = 'List the rule pack\'s parts and rules and their sign-off';

    public function handle(): int
    {
        $this->line('Rule pack '.config('tax.version').' · '.(RuleReview::packReviewed() ? 'reviewed' : 'not yet reviewed'));
        $this->table(['Part', 'Version', 'Signed off'], array_map(
            fn (array $part) => [$part['title'], $part['version'], $this->signed($part['reviewed'])],
            RuleReview::parts(),
        ));
        $rules = RuleReview::rules();
        $this->table(['Rule', 'Signed off'], array_map(
            fn (string $id, ?array $signOff) => [$id, $this->signed($signOff)],
            array_keys($rules),
            $rules,
        ));

        $left = count(array_filter(RuleReview::parts(), fn (array $p) => $p['reviewed'] === null)) + count(array_filter($rules, fn (?array $s) => $s === null));

        if ($left > 0) {
            $this->warn("{$left} still to sign off. Record each as ['by' => …, 'on' => 'YYYY-MM-DD', 'source' => …] in config/tax.php (parts) or resources/rule-pack/citations.php (rules).");
        }

        return $this->option('strict') && $left > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array{by: string, on: string, source: string}|null  $signOff
     */
    private function signed(?array $signOff): string
    {
        return $signOff === null ? '—' : "{$signOff['by']}, {$signOff['on']} ({$signOff['source']})";
    }
}
