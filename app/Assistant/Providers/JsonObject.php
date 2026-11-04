<?php

namespace App\Assistant\Providers;

/**
 * A JSON object in a model's reply: the reply itself, or the object written
 * inside it when a provider adds words around it.
 */
final class JsonObject
{
    /**
     * @return array<string, mixed>|null
     */
    public static function decode(string $text): ?array
    {
        $decoded = json_decode(trim($text), true);

        if (! is_array($decoded)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            $decoded = $start !== false && $end !== false && $end > $start ? json_decode(substr($text, $start, $end - $start + 1), true) : null;
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        $object = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $object[$key] = $value;
            }
        }

        return $object;
    }
}
