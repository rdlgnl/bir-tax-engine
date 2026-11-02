import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { peso } from '@/lib/format';
import type { WithholdingAtc } from '@/types';

type Kind = WithholdingAtc['kind'] | 'none';
type Payee = WithholdingAtc['payee'];

/** The tax kept from a payment under its ATC, to the centavo. */
export function agentWithheld(
    atcs: WithholdingAtc[],
    atc: string,
    amount: number,
): number {
    const rate = atcs.find((option) => option.code === atc)?.rate ?? 0;

    return Math.round(amount * rate * 100) / 100;
}

type Props = {
    atcs: WithholdingAtc[];
    atc: string;
    onChange: (atc: string) => void;
    amount: number;
    payee: string;
    error?: string;
};

/**
 * "I paid someone": for a withholding agent, the kind of payment picks the
 * ATC, and the tax to keep and send to the BIR comes off what the payee gets.
 */
export function AgentWithholding({
    atcs,
    atc,
    onChange,
    amount,
    payee,
    error,
}: Props) {
    const current = atcs.find((option) => option.code === atc);
    const [kind, setKind] = useState<Kind>(current?.kind ?? 'none');
    const [to, setTo] = useState<Payee>(current?.payee ?? 'person');
    const [declaration, setDeclaration] = useState<boolean>(
        current?.declaration ?? true,
    );
    const asksDeclaration = kind === 'professional';

    function choose(next: { kind?: Kind; to?: Payee; declaration?: boolean }) {
        const k = next.kind ?? kind;
        const p = next.to ?? to;
        const d = next.declaration ?? declaration;
        setKind(k);
        setTo(p);
        setDeclaration(d);

        if (k === 'none') {
            return onChange('');
        }

        const match = atcs.find(
            (option) =>
                option.kind === k &&
                option.payee === p &&
                (k !== 'professional' || option.declaration === d),
        );
        onChange(match?.code ?? '');
    }

    const withheld = agentWithheld(atcs, atc, amount);
    const rate = current ? Math.round(current.rate * 100) : 0;

    return (
        <div className="flex flex-col gap-3 rounded-2xl bg-muted p-4">
            <div className="flex flex-col gap-1.5">
                <span className="text-sm font-bold">
                    Did you withhold tax from this payment?
                </span>
                <ChoiceChips<Kind>
                    label="Did you withhold tax from this payment?"
                    value={kind}
                    onChange={(value) => choose({ kind: value })}
                    options={[
                        { value: 'none', label: 'No' },
                        { value: 'professional', label: 'Professional fee' },
                        { value: 'rent', label: 'Rent' },
                        { value: 'contractor', label: 'Contractor' },
                    ]}
                />
            </div>

            {kind !== 'none' && (
                <>
                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">
                            Person or company?
                        </span>
                        <ChoiceChips<Payee>
                            label="Person or company?"
                            variant="segmented"
                            value={to}
                            onChange={(value) => choose({ to: value })}
                            options={[
                                { value: 'person', label: 'Person' },
                                { value: 'company', label: 'Company' },
                            ]}
                            className="bg-slate-200/70"
                        />
                    </div>

                    {asksDeclaration && (
                        <div className="flex flex-col gap-1.5">
                            <span className="text-sm font-bold">
                                {to === 'person'
                                    ? 'Did they give you a sworn declaration this year?'
                                    : 'Did they declare gross income of ₱720,000 or less this year?'}
                            </span>
                            <ChoiceChips<'yes' | 'no'>
                                label={
                                    to === 'person'
                                        ? 'Did they give you a sworn declaration this year?'
                                        : 'Did they declare gross income of ₱720,000 or less this year?'
                                }
                                variant="segmented"
                                value={declaration ? 'yes' : 'no'}
                                onChange={(value) =>
                                    choose({ declaration: value === 'yes' })
                                }
                                options={[
                                    { value: 'yes', label: 'Yes' },
                                    {
                                        value: 'no',
                                        label:
                                            to === 'person'
                                                ? 'No, VAT-registered, or not sure'
                                                : 'No, or not sure',
                                    },
                                ]}
                                className="bg-slate-200/70"
                            />
                            <p className="text-[13px] text-muted-foreground">
                                {to === 'person'
                                    ? "With it, you withhold 5%. Without it, or if they're VAT-registered, 10%."
                                    : 'If so, you withhold 10%. Over ₱720,000, or not declared, 15%.'}
                            </p>
                        </div>
                    )}

                    {current && (
                        <dl className="rounded-2xl bg-ink px-5 py-4 text-ink-foreground">
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-[15px] text-slate-300">
                                    Keep and send to BIR ({rate}%)
                                </dt>
                                <dd className="text-2xl font-extrabold tabular-nums">
                                    {peso(withheld)}
                                </dd>
                            </div>
                            <div className="mt-1 flex items-center justify-between gap-3">
                                <dt className="text-[15px] text-slate-300">
                                    Pay {payee.trim() || 'them'}
                                </dt>
                                <dd className="text-2xl font-extrabold tabular-nums">
                                    {peso(Math.max(0, amount - withheld))}
                                </dd>
                            </div>
                            <p className="mt-2 text-xs text-slate-400">
                                {current.code} · {current.label}
                            </p>
                        </dl>
                    )}
                </>
            )}
            <FieldError message={error} />
        </div>
    );
}
