<?php

namespace App\Assistant\Providers;

use App\Assistant\Services\TaxLookups;

/**
 * One answer: its text, the conversation with it appended, and the figures
 * the lookups returned on the way.
 *
 * @phpstan-import-type Figure from TaxLookups
 */
final readonly class Turn
{
    /**
     * @param  list<array<string, mixed>>  $history
     * @param  list<Figure>  $figures
     */
    public function __construct(
        public string $text,
        public array $history,
        public array $figures,
    ) {}
}
