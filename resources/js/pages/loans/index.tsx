import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Plus } from 'lucide-react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { create, show } from '@/routes/loans';
import type { Loan } from '@/types';

const statuses: Record<Loan['dst']['status'], [string, string]> = {
    bank: ['The bank files it', 'bg-muted text-muted-foreground'],
    due: ['Form 2000 due', 'bg-warning-soft text-warning'],
    overdue: ['Form 2000 late', 'bg-red-50 text-destructive'],
    filed: ['Filed', 'bg-success-soft text-success'],
};

export default function Loans({ loans }: { loans: Loan[] }) {
    return (
        <>
            <Head title="Loans" />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Loans you signed
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Not income, but each owes documentary stamp tax
                    </p>
                </div>
                <Button asChild className="h-11 rounded-xl px-5 font-bold">
                    <Link href={create().url}>
                        <Plus className="size-5" aria-hidden />
                        Add a loan
                    </Link>
                </Button>
            </header>

            <SectionCard>
                {loans.length === 0 ? (
                    <p className="py-2 text-sm text-muted-foreground">
                        No loans yet. When you sign one, add it here to see the
                        DST and get Form 2000 filled.
                    </p>
                ) : (
                    <ul className="divide-y divide-divider">
                        {loans.map((loan) => {
                            const [label, tone] = statuses[loan.dst.status];

                            return (
                                <li key={loan.id}>
                                    <Link
                                        href={show(loan.id).url}
                                        className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                {peso(loan.amount)} from{' '}
                                                {loan.lender}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                Signed{' '}
                                                {formatDate(
                                                    loan.signed_on,
                                                    'long',
                                                )}{' '}
                                                · DST {peso(loan.dst.tax)}
                                                {loan.dst.status === 'due' &&
                                                    ` · due ${formatDate(loan.dst.due_date)}`}
                                            </span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span
                                                className={cn(
                                                    'rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap',
                                                    tone,
                                                )}
                                            >
                                                {label}
                                            </span>
                                            <ChevronRight
                                                className="size-4 text-muted-foreground"
                                                aria-hidden
                                            />
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </SectionCard>
        </>
    );
}
