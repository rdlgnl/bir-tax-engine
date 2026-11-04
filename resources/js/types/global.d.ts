import type { NextDeadline, Taxpayer } from '@/types/profile';
import type { TransactionFormContext } from '@/types/transactions';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            issuesUrl: string;
            taxpayer: Taxpayer | null;
            nextDeadline: NextDeadline | null;
            transactionForm: TransactionFormContext | null;
            appLock: { lock_after: number } | null;
            reminders: boolean;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: string;
        };
    }
}
