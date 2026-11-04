<?php

namespace App\Desktop;

use Illuminate\Encryption\Encrypter;

/**
 * The desktop app's own encryption key, made on its first run and kept in
 * its data folder. A packaged build carries the builder's .env: without
 * this, every install would share one APP_KEY, and what is encrypted with it
 * (the assistant's API key, the session, cookies) would open with any copy
 * of the app.
 */
final readonly class DeviceKey
{
    public function __construct(private string $path) {}

    /**
     * The key, as APP_KEY writes it: made and saved the first time.
     */
    public function get(string $cipher): string
    {
        $saved = is_file($this->path) ? trim((string) file_get_contents($this->path)) : '';

        if ($this->valid($saved, $cipher)) {
            return $saved;
        }

        $key = 'base64:'.base64_encode(Encrypter::generateKey($cipher));

        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0700, true);
        }

        file_put_contents($this->path, $key, LOCK_EX);
        chmod($this->path, 0600);

        return $key;
    }

    private function valid(string $key, string $cipher): bool
    {
        if (! str_starts_with($key, 'base64:')) {
            return false;
        }

        $raw = base64_decode(substr($key, 7), true);

        return $raw !== false && Encrypter::supported($raw, $cipher);
    }
}
