import { router } from '@inertiajs/react';
import { external } from '@/routes';

/**
 * Opens a link outside the app: a report on the issue tracker, or an email
 * draft. In the desktop app the window keeps to the app's own pages, so the
 * app hands the link to the computer's browser or email app; in a browser it
 * opens as any link would.
 */
export function openExternal(url: string, desktop: boolean): void {
    if (desktop) {
        router.post(
            external().url,
            { url },
            { preserveScroll: true, preserveState: true },
        );

        return;
    }

    if (url.startsWith('mailto:')) {
        window.location.href = url;
    } else {
        window.open(url, '_blank', 'noopener,noreferrer');
    }
}
