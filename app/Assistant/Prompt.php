<?php

namespace App\Assistant;

/**
 * What the assistant is told before every conversation. It never changes
 * within a conversation: the providers check that the history they're sent
 * is the one they answered, and cache it.
 */
final class Prompt
{
    private static ?string $system = null;

    public static function system(): string
    {
        return self::$system ??= trim((string) file_get_contents(resource_path('assistant/system-prompt.md')));
    }
}
