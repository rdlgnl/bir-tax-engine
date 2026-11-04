<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

/**
 * The desktop app (NativePHP): one window on the app, kept to the app's own
 * pages. A link out, to the issue tracker or an email draft, goes to the
 * taxpayer's browser or email app through ExternalLinkController.
 */
class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     */
    public function boot(): void
    {
        Window::open()
            ->title('BuwisBuddy')
            ->width(1280)
            ->height(860)
            ->minWidth(980)
            ->minHeight(640)
            ->rememberState()
            ->preventLeaveDomain();
    }

    /**
     * @return array<string, string>
     */
    public function phpIni(): array
    {
        return [
            // Filled BIR forms and the books are drawn in PHP.
            'memory_limit' => '512M',
            // An assistant's answer, or a letter it reads, can take minutes.
            'max_execution_time' => '300',
            // Statements, certificates and letters up to 10 MB.
            'upload_max_filesize' => '12M',
            'post_max_size' => '14M',
        ];
    }
}
