<?php

namespace App\Assistant\Providers;

use App\Assistant\Services\Suggestions;
use App\Assistant\Services\TaxLookups;

/**
 * One answer: its text, the conversation with it appended, the figures the
 * lookups returned on the way, and what it suggested for the taxpayer to approve.
 *
 * @phpstan-import-type Figure from TaxLookups
 * @phpstan-import-type Suggestion from Suggestions
 */
final readonly class Turn
{
    /**
     * @param  list<array<string, mixed>>  $history
     * @param  list<Figure>  $figures
     * @param  list<Suggestion>  $suggestions
     */
    public function __construct(
        public string $text,
        public array $history,
        public array $figures,
        public array $suggestions = [],
    ) {}
}
