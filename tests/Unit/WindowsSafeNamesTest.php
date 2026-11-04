<?php

use Symfony\Component\Process\Process;

/*
 * The release workflow builds the desktop app on Windows too, and Git on
 * Windows can't check out a file whose name Windows doesn't allow: the
 * whole checkout fails (exit code 128).
 */
test('every file in the repository can be checked out on Windows', function () {
    $git = new Process(['git', 'ls-files', '-z'], dirname(__DIR__, 2));
    $git->run();

    if (! $git->isSuccessful()) {
        $this->markTestSkipped('Not a Git checkout.');
    }

    $files = array_filter(explode("\0", $git->getOutput()));
    $unsafe = array_values(array_filter($files, function (string $path): bool {
        // The runner checks out under D:\a\bir-tax-engine\bir-tax-engine\, and Windows stops at 260.
        if (strlen($path) > 200) {
            return true;
        }

        foreach (explode('/', $path) as $part) {
            if (preg_match('/[<>:"\\\\|?*\x00-\x1F]/', $part) === 1
                || preg_match('/[. ]$/', $part) === 1
                || preg_match('/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(\..*)?$/i', $part) === 1) {
                return true;
            }
        }

        return false;
    }));

    expect($files)->not->toBeEmpty()
        ->and($unsafe)->toBe([]);
});
