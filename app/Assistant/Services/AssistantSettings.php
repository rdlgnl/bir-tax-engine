<?php

namespace App\Assistant\Services;

use App\Data\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The assistant's settings on this device. Off by default; when on, the
 * taxpayer's own API key for the provider they chose, kept encrypted with
 * the app's key. Like every device setting it stays out of backups, and
 * "Erase all data" removes it.
 */
final readonly class AssistantSettings
{
    public const PROVIDERS = ['claude', 'openai'];

    /** The Claude models offered; the first is the default. */
    public const CLAUDE_MODELS = [
        'claude-opus-5-5' => 'Claude Opus 5.5',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
        'claude-haiku-5-5' => 'Claude Haiku 5.5',
    ];

    public const OPENAI_BASE_URL = 'https://api.openai.com/v1';

    public function __construct(
        public bool $enabled,
        public string $provider,
        public string $model,
        public string $baseUrl,
        private ?string $encryptedKey,
    ) {}

    public static function current(): self
    {
        $saved = (array) Setting::get('assistant', []);
        $provider = in_array($saved['provider'] ?? null, self::PROVIDERS, true) ? $saved['provider'] : 'claude';

        return new self(
            enabled: (bool) ($saved['enabled'] ?? false),
            provider: $provider,
            model: (string) ($saved['model'] ?? ($provider === 'claude' ? array_key_first(self::CLAUDE_MODELS) : '')),
            baseUrl: (string) ($saved['base_url'] ?? self::OPENAI_BASE_URL),
            encryptedKey: is_string($saved['key'] ?? null) ? $saved['key'] : null,
        );
    }

    /**
     * Saves the settings. A blank key keeps the one saved; $removeKey forgets it.
     *
     * @param  array{enabled: bool, provider: string, model: string, base_url: string|null}  $values
     */
    public function save(array $values, ?string $key, bool $removeKey = false): void
    {
        Setting::put('assistant', [
            'enabled' => $values['enabled'],
            'provider' => $values['provider'],
            'model' => $values['model'],
            'base_url' => $values['provider'] === 'openai' ? rtrim((string) $values['base_url'], '/') : self::OPENAI_BASE_URL,
            'key' => match (true) {
                $removeKey => null,
                $key !== null && $key !== '' => Crypt::encryptString($key),
                default => $this->encryptedKey,
            },
        ]);
    }

    /**
     * The API key, or null when none is saved or it can't be read (the app
     * key changed, as after moving to another computer).
     */
    public function key(): ?string
    {
        if ($this->encryptedKey === null) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encryptedKey);
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasKey(): bool
    {
        return $this->key() !== null;
    }

    /**
     * On, with what it needs to answer. An OpenAI-compatible server on this
     * computer may need no key.
     */
    public function ready(): bool
    {
        return $this->enabled && $this->model !== '' && ($this->hasKey() || $this->provider === 'openai');
    }

    /** A different provider or model starts a new conversation. */
    public function conversation(): string
    {
        return "{$this->provider}|{$this->baseUrl}|{$this->model}";
    }

    public function label(): string
    {
        return $this->provider === 'claude'
            ? self::CLAUDE_MODELS[$this->model] ?? $this->model
            : $this->model.' at '.(parse_url($this->baseUrl, PHP_URL_HOST) ?: $this->baseUrl);
    }

    /**
     * What the settings card shows: never the key itself.
     *
     * @return array{enabled: bool, provider: string, model: string, base_url: string, has_key: bool, claude_models: list<array{value: string, label: string}>}
     */
    public function toProps(): array
    {
        return [
            'enabled' => $this->enabled,
            'provider' => $this->provider,
            'model' => $this->model,
            'base_url' => $this->baseUrl,
            'has_key' => $this->hasKey(),
            'claude_models' => array_map(fn (string $id) => ['value' => $id, 'label' => self::CLAUDE_MODELS[$id]], array_keys(self::CLAUDE_MODELS)),
        ];
    }
}
