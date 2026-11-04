<?php

namespace App\Assistant\Providers;

use App\Assistant\Services\AssistantSettings;

/**
 * The provider the settings name.
 */
class ProviderFactory
{
    public function for(AssistantSettings $settings): Provider
    {
        return match ($settings->provider) {
            'openai' => new OpenAiProvider($settings->key(), $settings->model, $settings->baseUrl),
            default => new ClaudeProvider((string) $settings->key(), $settings->model),
        };
    }
}
