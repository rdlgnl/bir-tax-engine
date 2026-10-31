import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { now as lockNow } from '@/routes/lock';

const ACTIVITY = [
    'pointerdown',
    'pointermove',
    'keydown',
    'scroll',
    'touchstart',
];

/**
 * Locks the app after the chosen minutes with nobody using this window.
 * The server locks too; this hides the records on screen at the same time.
 */
export function useIdleLock(lockAfterMinutes: number | null) {
    useEffect(() => {
        if (lockAfterMinutes === null) {
            return;
        }

        let timer = 0;
        const restart = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(
                () => router.post(lockNow().url),
                lockAfterMinutes * 60_000,
            );
        };

        restart();
        ACTIVITY.forEach((event) =>
            window.addEventListener(event, restart, { passive: true }),
        );

        return () => {
            window.clearTimeout(timer);
            ACTIVITY.forEach((event) =>
                window.removeEventListener(event, restart),
            );
        };
    }, [lockAfterMinutes]);
}
