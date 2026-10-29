import { router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useEffect, useState } from 'react';

/** Shows the one-off message a controller sends with Inertia::flash('toast', ...). */
export function FlashToast() {
    const [message, setMessage] = useState<string | null>(null);

    useEffect(
        () =>
            router.on('flash', (event) => {
                const toast = event.detail.flash.toast;

                if (toast) {
                    setMessage(toast);
                }
            }),
        [],
    );

    useEffect(() => {
        if (!message) {
            return;
        }

        const timer = setTimeout(() => setMessage(null), 4000);

        return () => clearTimeout(timer);
    }, [message]);

    return (
        <div
            role="status"
            aria-live="polite"
            className="pointer-events-none fixed inset-x-4 bottom-24 z-50 flex justify-center lg:bottom-8"
        >
            {message && (
                <div className="flex max-w-md animate-in items-center gap-2.5 rounded-xl bg-ink px-4 py-3 text-sm font-semibold text-ink-foreground shadow-lg fade-in slide-in-from-bottom-2">
                    <CheckCircle2
                        className="size-5 shrink-0 text-emerald-400"
                        aria-hidden
                    />
                    {message}
                </div>
            )}
        </div>
    );
}
