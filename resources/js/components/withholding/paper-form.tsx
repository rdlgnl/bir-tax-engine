import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type PaperRow = {
    label: ReactNode;
    value: ReactNode;
    total?: boolean;
};

export type PaperPart = {
    title: string;
    rows: PaperRow[];
    /** Two label/value pairs per line, like Part I of the BIR forms. */
    split?: boolean;
};

/**
 * A simplified BIR form laid out like paper, for forms built from the
 * profile rather than a return snapshot. Yellow marks what the app filled.
 */
export function PaperForm({
    form,
    title,
    parts,
    edition,
}: {
    form: string;
    title: string;
    parts: PaperPart[];
    edition: string;
}) {
    return (
        <article
            aria-label={`Filled BIR Form ${form}`}
            className="rounded-sm bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,0.08)] sm:p-8"
        >
            <header className="flex items-end justify-between gap-4 border-b-2 border-ink pb-3">
                <div>
                    <p className="text-xs text-slate-600">
                        Republic of the Philippines · Bureau of Internal Revenue
                    </p>
                    <h2 className="text-lg leading-snug font-extrabold">
                        {title}
                    </h2>
                </div>
                <p className="shrink-0 text-right text-sm font-extrabold">
                    BIR Form No.
                    <br />
                    {form}
                </p>
            </header>

            {parts.map((part) => (
                <section key={part.title}>
                    <h3 className="mt-4 mb-2 text-xs font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                        {part.title}
                    </h3>
                    <dl
                        className={cn(
                            'grid border text-sm',
                            part.split && 'sm:grid-cols-2',
                        )}
                    >
                        {part.rows.map((row, i) => (
                            <div
                                key={i}
                                className={cn(
                                    'flex items-center justify-between gap-3 border-b border-divider px-3 py-2',
                                    part.split
                                        ? 'sm:odd:border-r'
                                        : 'last:border-0',
                                    row.total && 'font-bold',
                                )}
                            >
                                <dt>{row.label}</dt>
                                <dd className="rounded-sm bg-[#FEF3B3] px-1.5 py-0.5 text-right font-mono text-[13px] font-bold">
                                    {row.value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>
            ))}

            <p className="mt-6 text-sm text-slate-600">
                Signature over printed name: ______________________
            </p>
            <p className="mt-4 border-t border-dashed pt-3 text-xs text-slate-600">
                Yellow = filled by the app. This preview is simplified; the
                downloaded PDF is the BIR's own {form}, {edition} edition.
            </p>
        </article>
    );
}
