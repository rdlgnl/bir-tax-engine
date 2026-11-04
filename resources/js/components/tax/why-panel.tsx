import { AlertTriangle, Check, X } from 'lucide-react';
import { ReportMistakeLink } from '@/components/report-mistake-link';
import { SectionCard } from '@/components/section-card';
import type { Explanation, RulePack } from '@/types';

type Props = {
    explanation: Explanation;
    regimeLabel: string | null;
    rulePack: RulePack;
};

/** "Why is this my tax?": the answers and BIR rules behind the number. */
export function WhyPanel({ explanation, regimeLabel, rulePack }: Props) {
    return (
        <SectionCard as="section" className="flex flex-col gap-4">
            <h2 className="text-xl font-extrabold">Why is this my tax?</h2>

            <div>
                <p className="mb-2 text-[15px] text-muted-foreground">
                    {regimeLabel} applies because your profile shows:
                </p>
                <ul className="flex flex-col gap-2">
                    {explanation.reasons.map((reason) => (
                        <li
                            key={reason.text}
                            className="flex items-start gap-2.5 text-[15px]"
                        >
                            {reason.applies ? (
                                <Check
                                    className="mt-0.5 size-4 shrink-0 text-success"
                                    aria-label="Yes"
                                />
                            ) : (
                                <X
                                    className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-label="No"
                                />
                            )}
                            <span className="flex-1 font-semibold">
                                {reason.text}
                            </span>
                            <span className="text-[13px] text-muted-foreground">
                                {reason.source}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="flex flex-col gap-2.5">
                <h3 className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700">
                    LEGAL BASIS
                </h3>
                {explanation.rules.map((rule) => (
                    <div
                        key={rule.id + rule.title}
                        className="rounded-xl bg-muted px-3.5 py-3"
                    >
                        <p className="text-[15px] font-bold">{rule.title}</p>
                        <p className="text-sm text-slate-700">{rule.basis}</p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {rule.detail ?? `Rule ${rule.id} v${rule.version}`}{' '}
                            · since {new Date(rule.effective).getFullYear()} ·{' '}
                            {rule.reviewed
                                ? `reviewed by ${rule.reviewed.by}`
                                : 'not yet reviewed'}{' '}
                            ·{' '}
                            <ReportMistakeLink
                                packVersion={rulePack.version}
                                rule={rule}
                                label="report a mistake"
                            />
                        </p>
                    </div>
                ))}
            </div>

            {explanation.skipped.length > 0 && (
                <details className="rounded-xl border px-3.5 py-3">
                    <summary className="cursor-pointer text-[15px] font-bold">
                        Checked but not applied ({explanation.skipped.length})
                    </summary>
                    <ul className="mt-2 flex flex-col gap-1.5 text-sm">
                        {explanation.skipped.map((rule) => (
                            <li key={rule.id}>
                                <strong>{rule.title}</strong>: {rule.reason}
                            </li>
                        ))}
                    </ul>
                </details>
            )}

            {explanation.warnings.length > 0 && (
                <div className="flex items-start gap-2.5 rounded-xl bg-warning-soft px-3.5 py-3 text-sm text-warning">
                    <AlertTriangle
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden
                    />
                    <div>
                        <p className="font-bold">Could change:</p>
                        <ul className="list-disc pl-4">
                            {explanation.warnings.map((warning) => (
                                <li key={warning}>{warning}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}

            <p className="text-xs text-muted-foreground">
                Rule pack {rulePack.version}
                {rulePack.reviewed
                    ? ' · reviewed by a tax professional'
                    : ' · not yet reviewed by a tax professional'}{' '}
                · <ReportMistakeLink packVersion={rulePack.version} />
            </p>
        </SectionCard>
    );
}
