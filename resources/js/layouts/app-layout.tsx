import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AppSidebar } from '@/components/app-sidebar';
import { FlashToast } from '@/components/flash-toast';
import { MobileTabBar } from '@/components/mobile-tab-bar';
import { AddTransactionPanel } from '@/components/transactions/add-transaction-panel';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import { useDeadlineReminders } from '@/hooks/use-deadline-reminders';
import { useIdleLock } from '@/hooks/use-idle-lock';

/** Sidebar on desktop, bottom tab bar with an "Add" sheet on phones. */
export default function AppLayout({ children }: { children: ReactNode }) {
    const [adding, setAdding] = useState(false);
    const { appLock, reminders, nextDeadline } = usePage().props;

    useIdleLock(appLock?.lock_after ?? null);
    useDeadlineReminders(reminders, nextDeadline);

    return (
        <div className="flex min-h-dvh bg-background">
            <AppSidebar />
            <main className="min-w-0 flex-1 px-4 pt-6 pb-28 sm:px-6 lg:px-8 lg:pt-8 lg:pb-10">
                {children}
            </main>

            <MobileTabBar onAdd={() => setAdding(true)} />
            <Sheet open={adding} onOpenChange={setAdding}>
                <SheetContent
                    side="bottom"
                    className="max-h-[92dvh] overflow-y-auto rounded-t-[22px] px-4 pt-6 pb-8"
                >
                    <SheetTitle className="sr-only">
                        Add a transaction
                    </SheetTitle>
                    <SheetDescription className="sr-only">
                        Record money you received, an invoice, an expense or a
                        2307.
                    </SheetDescription>
                    <AddTransactionPanel onDone={() => setAdding(false)} />
                </SheetContent>
            </Sheet>

            <FlashToast />
        </div>
    );
}
