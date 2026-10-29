import { formAmount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReturnLayout } from '@/types';

/** The filled form, laid out like paper. Yellow marks what the app filled. */
export function FormSheet({
    layout,
    compact = false,
}: {
    layout: ReturnLayout;
    compact?: boolean;
}) {
    const fill =
        'rounded-sm bg-[#FEF3B3] px-1.5 py-0.5 font-mono text-[13px] font-bold';

    return (
        <article
            aria-label={`Filled BIR Form ${layout.form}`}
            className={cn(
                'rounded-sm bg-white shadow-[0_2px_12px_rgba(15,23,42,0.08)]',
                compact ? 'p-5' : 'p-8',
            )}
        >
            <header className="flex items-end justify-between gap-4 border-b-2 border-ink pb-3">
                <div>
                    <p className="text-xs text-slate-600">
                        Republic of the Philippines · Bureau of Internal Revenue
                    </p>
                    <h2 className="text-lg font-extrabold">{layout.title}</h2>
                    <p className="text-xs text-slate-600">
                        For individuals · {layout.method}
                    </p>
                </div>
                <p className="text-right text-sm font-extrabold">
                    BIR Form No.
                    <br />
                    {layout.form}
                </p>
            </header>

            <h3 className="mt-4 mb-2 text-xs font-extrabold tracking-[0.4px] text-slate-700">
                PART I · BACKGROUND INFORMATION
            </h3>
            <dl className="grid border text-sm sm:grid-cols-2">
                {[
                    ['For the year · quarter', layout.period],
                    ['ATC', layout.taxpayer.atc],
                    ['TIN', layout.taxpayer.tin ?? 'Not registered'],
                    ['RDO code', layout.taxpayer.rdo ?? '—'],
                    ['Taxpayer name', layout.taxpayer.name],
                    ['Amended return?', '☐ Yes ☑ No'],
                ].map(([label, value]) => (
                    <div
                        key={label}
                        className="flex items-center justify-between gap-3 border-b border-divider px-3 py-2 sm:odd:border-r"
                    >
                        <dt>{label}</dt>
                        <dd className={fill}>{value}</dd>
                    </div>
                ))}
            </dl>

            <h3 className="mt-4 mb-2 text-xs font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                Part II · Computation of tax ({layout.method})
            </h3>
            <dl className="border text-sm">
                {layout.lines.map((line) => (
                    <div
                        key={line.label}
                        className={cn(
                            'flex items-center justify-between gap-3 border-b border-divider px-3 py-2 last:border-0',
                            line.kind === 'total' && 'font-bold',
                        )}
                    >
                        <dt>{line.label}</dt>
                        <dd className={fill}>{formAmount(line.amount)}</dd>
                    </div>
                ))}
            </dl>

            {!compact && (
                <p className="mt-6 text-sm text-slate-600">
                    Signature over printed name: ______________________
                </p>
            )}
            <p className="mt-4 border-t border-dashed pt-3 text-xs text-slate-600">
                Yellow = filled by the app. This is a print-ready summary that
                follows the form's wording, not the official form: copy the
                values into eBIRForms or the official {layout.form}.
            </p>
        </article>
    );
}
