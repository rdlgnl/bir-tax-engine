import type { NextDeadline, Taxpayer } from '@/types/profile';
import type { TransactionFormContext } from '@/types/transactions';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            taxpayer: Taxpayer | null;
            nextDeadline: NextDeadline | null;
            transactionForm: TransactionFormContext | null;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: string;
        };
    }
}
