import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { SectionCard } from '@/components/section-card';
import { formatDate, peso } from '@/lib/format';
import { show } from '@/routes/corporation';
import type { CorporateQuarterTax } from '@/types';

export type HomeCorporation = {
    name: string;
    next: Pick<
        CorporateQuarterTax,
        'quarter' | 'has_figures' | 'payable' | 'due_date' | 'status'
    > | null;
};

/** A corporation the taxpayer runs, and where its next 1702Q stands. */
export function CorporationCard({
    corporation,
}: {
    corporation: HomeCorporation;
}) {
    const next = corporation.next;

    return (
        <SectionCard title="Your corporation" className="self-start">
            <Link
                href={show().url}
                className="flex items-center justify-between gap-3 hover:underline"
            >
                <span>
                    <span className="block text-[15px] font-bold">
                        {corporation.name}
                    </span>
                    <span
                        className={
                            next?.status === 'overdue'
                                ? 'text-sm text-destructive'
                                : 'text-sm text-muted-foreground'
                        }
                    >
                        {next === null
                            ? 'No 1702Q to file right now'
                            : next.has_figures
                              ? `Q${next.quarter} 1702Q · ${peso(Math.max(0, next.payable))} due ${formatDate(next.due_date, 'short')}`
                              : `Q${next.quarter} 1702Q due ${formatDate(next.due_date, 'short')} · enter its figures`}
                    </span>
                </span>
                <ChevronRight
                    className="size-4 text-muted-foreground"
                    aria-hidden
                />
            </Link>
        </SectionCard>
    );
}
