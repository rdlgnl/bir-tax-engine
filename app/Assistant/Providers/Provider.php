<?php

namespace App\Assistant\Providers;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Services\TaxLookups;

/**
 * An AI provider the assistant asks, through the taxpayer's own key.
 */
interface Provider
{
    /**
     * Answers a question, looking figures up as it needs them.
     *
     * @param  list<array<string, mixed>>  $history  the conversation so far, in this provider's own format, exactly as it was
     *
     * @throws AssistantUnavailable when the provider refuses the key, can't be reached or fails
     */
    public function ask(array $history, string $question, TaxLookups $lookups): Turn;
}
