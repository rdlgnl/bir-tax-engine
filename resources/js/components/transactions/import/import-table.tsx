import { useState } from 'react';
import { SectionCard } from '@/components/section-card';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Answer, Row, Status } from './types';

const statusLabels: Record<Status, string> = {
    import: 'Import',
    settles: 'Marks invoice paid',
    answer: 'Waiting',
    duplicate: 'Already recorded',
    ignore: 'Ignore',
    filed: 'Quarter filed',
    invalid: 'Can’t read',
};

/** Every line of the statement and what happens to it: the first 25 until asked for all. */
export function ImportTable({
    rows,
    answers,
}: {
    rows: Row[];
    answers: Record<number, Answer>;
}) {
    const [showAll, setShowAll] = useState(false);
    const shown = showAll ? rows : rows.slice(0, 25);

    return (
        <SectionCard>
            <div className="-mx-1 overflow-x-auto">
                <table className="w-full text-sm tabular-nums">
                    <thead>
                        <tr className="border-b text-left text-xs font-bold tracking-[0.3px] text-slate-600 uppercase">
                            <th className="px-1.5 py-2">Date</th>
                            <th className="px-1.5 py-2">From statement</th>
                            <th className="px-1.5 py-2">Matched to</th>
                            <th className="px-1.5 py-2 text-right">Amount</th>
                            <th className="px-1.5 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {shown.map((row) => (
                            <ImportRow
                                key={row.index}
                                row={row}
                                answer={answers[row.index]}
                            />
                        ))}
                    </tbody>
                </table>
            </div>
            {rows.length > shown.length && (
                <p className="mt-3 text-sm text-muted-foreground">
                    Showing {shown.length} of {rows.length} lines ·{' '}
                    <button
                        type="button"
                        onClick={() => setShowAll(true)}
                        className="font-bold text-primary hover:underline"
                    >
                        Show all
                    </button>
                </p>
            )}
        </SectionCard>
    );
}

function ImportRow({ row, answer }: { row: Row; answer?: Answer }) {
    const status: Status = row.status;
    const answered = status === 'answer' && answer;

    return (
        <tr className="border-b border-divider">
            <td className="px-1.5 py-2.5 whitespace-nowrap">
                {row.date ? formatDate(row.date) : '—'}
            </td>
            <td className="px-1.5 py-2.5 uppercase">{row.description}</td>
            <td className="px-1.5 py-2.5">
                {status === 'import' ||
                status === 'settles' ||
                status === 'duplicate' ||
                status === 'filed' ? (
                    <span>
                        <span className="font-bold">{row.client}</span>
                        {row.reason &&
                            (status === 'import' || status === 'settles') && (
                                <span className="block text-xs text-muted-foreground">
                                    {row.reason}
                                </span>
                            )}
                    </span>
                ) : status === 'answer' ? (
                    <span className="font-bold text-warning">
                        {answered
                            ? answer.kind === 'payment'
                                ? answer.client || 'Who paid?'
                                : 'Personal'
                            : 'Needs answer'}
                    </span>
                ) : (
                    <span className="text-muted-foreground">
                        {status === 'ignore' ? 'Not income' : row.reason}
                    </span>
                )}
            </td>
            <td className="px-1.5 py-2.5 text-right whitespace-nowrap">
                {row.received < 0 ? '−' : ''}
                {peso(row.received)}
                {status === 'import' && row.withheld > 0 && (
                    <span className="block text-xs text-muted-foreground">
                        billed {peso(row.amount)}
                    </span>
                )}
            </td>
            <td
                className={cn(
                    'px-1.5 py-2.5 whitespace-nowrap',
                    (status === 'import' || status === 'settles') &&
                        'font-bold text-success',
                    status === 'answer' && 'font-bold text-warning',
                    !['import', 'settles', 'answer'].includes(status) &&
                        'text-muted-foreground',
                )}
            >
                {answered
                    ? answer.kind === 'payment'
                        ? 'Import'
                        : 'Ignore'
                    : statusLabels[status]}
            </td>
        </tr>
    );
}
