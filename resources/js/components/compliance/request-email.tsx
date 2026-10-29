import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import type { CertificateRow, QuarterResult } from '@/types';

type Props = {
    row: CertificateRow;
    year: number;
    quarter: QuarterResult | null;
};

/** A ready-to-send request for a missing 2307, written in the person's voice. */
export function RequestEmail({ row, year, quarter }: Props) {
    const { taxpayer } = usePage().props;
    const [copied, setCopied] = useState(false);

    const period = `${monthsLong(row.quarter)} ${year}`;
    const payment = row.payment_date
        ? `my ${peso(row.amount_paid)} payment on ${formatDate(row.payment_date)}`
        : `my payments that quarter (${peso(row.amount_paid)} in total)`;
    const deadline =
        quarter && quarter.status !== 'filed'
            ? ` I need it for my quarterly return due ${formatDate(quarter.due_date)}.`
            : '';
    const body =
        `Hi! Could you send me the BIR Form 2307 for ${period}? You withheld ${peso(row.amount)} from ${payment}.${deadline} Thank you, ${taxpayer?.first_name ?? ''}`.trim();
    const subject = `BIR Form 2307 for ${period}`;

    async function copy() {
        try {
            await navigator.clipboard.writeText(body);
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        } catch {
            setCopied(false);
        }
    }

    return (
        <div className="mt-4 rounded-2xl border border-dashed border-input p-4">
            <h3 className="text-xs font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                Draft email to {row.counterparty}
            </h3>
            <p className="mt-2 text-[15px]">{body}</p>
            <div className="mt-3 flex flex-wrap gap-2">
                <Button asChild className="rounded-xl font-bold">
                    <a
                        href={`mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`}
                    >
                        Open in my email
                    </a>
                </Button>
                <Button
                    variant="outline"
                    className="rounded-xl font-bold"
                    onClick={copy}
                >
                    {copied ? 'Copied' : 'Copy text'}
                </Button>
            </div>
            <span role="status" className="sr-only">
                {copied ? 'Email text copied' : ''}
            </span>
        </div>
    );
}

function monthsLong(quarter: number): string {
    const name = (month: number) =>
        new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
            new Date(2000, month - 1, 1),
        );

    return `${name(quarter * 3 - 2)}–${name(quarter * 3)}`;
}
