import { useEffect } from 'react';
import { formatDate, parseDate, peso } from '@/lib/format';
import type { NextDeadline } from '@/types';

/**
 * Deadline reminders as browser notifications, 7 days and 1 day before the
 * next due date. They appear when the app is open; each shows once.
 */
export function useDeadlineReminders(
    enabled: boolean,
    deadline: NextDeadline | null,
) {
    useEffect(() => {
        if (
            !enabled ||
            !deadline ||
            !('Notification' in window) ||
            Notification.permission !== 'granted'
        ) {
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const days = Math.round(
            (parseDate(deadline.date).getTime() - today.getTime()) / 86_400_000,
        );

        if (days < 0 || days > 7) {
            return;
        }

        const key = `reminded:${deadline.date}:${deadline.form}:${days <= 1 ? 1 : 7}`;

        try {
            if (localStorage.getItem(key)) {
                return;
            }

            localStorage.setItem(key, '1');
        } catch {
            // Without storage the reminder may repeat; that's acceptable.
        }

        new Notification(
            `${deadline.form} due ${days === 0 ? 'today' : days === 1 ? 'tomorrow' : formatDate(deadline.date, 'short')}`,
            {
                body:
                    deadline.amount !== null
                        ? `${peso(deadline.amount)} to pay. File and pay in BuwisBuddy.`
                        : 'Open BuwisBuddy to prepare it.',
            },
        );
    }, [enabled, deadline]);
}
