import { Link } from '@inertiajs/react';
import { SectionCard } from '@/components/section-card';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/tax';
import type { QuarterResult } from '@/types';

export function QuarterTable({
    quarters,
    selected,
}: {
    quarters: QuarterResult[];
    selected: number;
}) {
    return (
        <SectionCard title="By quarter">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[460px] text-sm">
                    <caption className="sr-only">Income tax by quarter</caption>
                    <thead>
                        <tr className="text-[13px] font-bold text-slate-700">
                            <th
                                scope="col"
                                className="py-2 text-left font-bold"
                            >
                                Quarter
                            </th>
                            <th
                                scope="col"
                                className="py-2 text-right font-bold"
                            >
                                Earned
                            </th>
                            <th
                                scope="col"
                                className="py-2 text-right font-bold"
                            >
                                Withheld
                            </th>
                            <th
                                scope="col"
                                className="py-2 text-right font-bold"
                            >
                                Paid in cash
                            </th>
                            <th
                                scope="col"
                                className="py-2 text-right font-bold"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {quarters.map((q) => (
                            <tr
                                key={q.quarter}
                                className={cn(
                                    'border-t border-divider',
                                    q.quarter === selected && 'font-extrabold',
                                )}
                            >
                                <th
                                    scope="row"
                                    className="py-3 text-left font-[inherit]"
                                >
                                    <Link
                                        href={show(q.quarter).url}
                                        className="hover:underline"
                                    >
                                        {q.quarter === 4
                                            ? 'Annual'
                                            : `Q${q.quarter}`}
                                    </Link>
                                </th>
                                <td className="py-3 text-right">
                                    {peso(q.quarter_gross)}
                                </td>
                                <td className="py-3 text-right">
                                    {peso(q.quarter_withheld)}
                                </td>
                                <td className="py-3 text-right">
                                    {peso(q.amount_paid ?? q.due)}
                                </td>
                                <td className="py-3 text-right">
                                    <Status quarter={q} />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </SectionCard>
    );
}

function Status({ quarter }: { quarter: QuarterResult }) {
    switch (quarter.status) {
        case 'filed':
            return (
                <span className="font-bold text-success">
                    Filed {formatDate(quarter.filed_on!)}
                </span>
            );
        case 'due':
            return (
                <span className="font-bold text-warning">
                    Due {formatDate(quarter.due_date)}
                </span>
            );
        case 'overdue':
            return <span className="font-bold text-destructive">Overdue</span>;
        default:
            return <span className="text-muted-foreground">In progress</span>;
    }
}
