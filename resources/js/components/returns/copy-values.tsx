import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formAmount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReturnLayout } from '@/types';

type Row = { label: string; value: string; total?: boolean };

/** "Filing online? Copy into 1701Q, top to bottom." */
export function CopyValues({ layout }: { layout: ReturnLayout }) {
    const [copied, setCopied] = useState<string | null>(null);
    const rows: Row[] = [
        { label: 'Year · quarter', value: layout.period },
        {
            label: 'TIN · RDO',
            value: [layout.taxpayer.tin ?? 'No TIN yet', layout.taxpayer.rdo]
                .filter(Boolean)
                .join(' · '),
        },
        { label: 'Tax code (ATC)', value: layout.taxpayer.atc },
        ...layout.lines.map((line) => ({
            label: line.label,
            value: formAmount(line.amount),
            total: line.kind === 'total',
        })),
    ];

    async function copy(key: string, text: string) {
        try {
            await navigator.clipboard.writeText(text);
            setCopied(key);
            setTimeout(() => setCopied(null), 2000);
        } catch {
            setCopied(null);
        }
    }

    return (
        <SectionCard>
            <div className="mb-2 flex items-center justify-between gap-3">
                <h2 className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                    Filing online? Copy into {layout.form}, top to bottom
                </h2>
                <Button
                    variant="outline"
                    size="sm"
                    className="rounded-xl font-bold"
                    onClick={() =>
                        copy(
                            'all',
                            rows
                                .map((r) => `${r.label}\t${r.value}`)
                                .join('\n'),
                        )
                    }
                >
                    {copied === 'all' ? 'Copied' : 'Copy all'}
                </Button>
            </div>
            <dl className="divide-y divide-divider">
                {rows.map((row) => (
                    <div
                        key={row.label}
                        className="flex items-center gap-3 py-2"
                    >
                        <dt
                            className={cn(
                                'flex-1 text-[15px]',
                                row.total && 'font-bold',
                            )}
                        >
                            {row.label}
                        </dt>
                        <dd className="font-mono text-[15px] font-bold">
                            {row.value}
                        </dd>
                        <button
                            type="button"
                            onClick={() =>
                                copy(row.label, row.value.replace(/,/g, ''))
                            }
                            aria-label={`Copy ${row.label}`}
                            className="flex size-9 items-center justify-center rounded-lg bg-muted text-primary hover:bg-accent"
                        >
                            {copied === row.label ? (
                                <Check className="size-4" aria-hidden />
                            ) : (
                                <Copy className="size-4" aria-hidden />
                            )}
                        </button>
                    </div>
                ))}
            </dl>
            <span role="status" className="sr-only">
                {copied ? 'Copied' : ''}
            </span>
            <p className="mt-3 text-[13px] text-muted-foreground">
                Labels follow the form's wording. Check each one against the
                field numbers on the current {layout.form}.
            </p>
        </SectionCard>
    );
}
