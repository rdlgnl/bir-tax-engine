import { Link } from '@inertiajs/react';
import { minusPeso, peso } from '@/lib/format';
import { SectionCard } from '@/components/section-card';
import { withholding } from '@/routes';
import type { QuarterResult } from '@/types';

type Props = {
    quarter: QuarterResult;
    regime: string | null;
    vatRegistered: boolean;
    withholdingAgent: boolean;
};

/** "This quarter · Q3": every tax and where it stands. */
export function QuarterCard({
    quarter,
    regime,
    vatRegistered,
    withholdingAgent,
}: Props) {
    const eightPercent = regime === 'eight_percent';
    const name = quarter.quarter === 4 ? 'Annual' : `Q${quarter.quarter}`;

    return (
        <SectionCard title={`This quarter · ${name}`}>
            <dl className="divide-y divide-divider">
                <Row
                    label={`Income tax${eightPercent ? ' (8%)' : ''}`}
                    value={
                        <span className="font-extrabold">
                            {peso(quarter.due)}
                        </span>
                    }
                />
                <Row
                    label="Percentage tax"
                    value={
                        quarter.percentage_tax === null ? (
                            <span className="text-muted-foreground">
                                {eightPercent
                                    ? 'Not needed on 8%'
                                    : 'Not applicable'}
                            </span>
                        ) : (
                            <span className="font-extrabold">
                                {peso(quarter.percentage_tax)}
                            </span>
                        )
                    }
                />
                <Row
                    label="VAT"
                    value={
                        <span className="text-muted-foreground">
                            {vatRegistered
                                ? 'Quarterly 2550Q, see VAT'
                                : 'Not registered'}
                        </span>
                    }
                />
                {withholdingAgent && (
                    <Row
                        label="Withheld from payees"
                        value={
                            <Link
                                href={withholding().url}
                                className="font-bold text-primary hover:underline"
                            >
                                0619-E, 1601-EQ, 2307s
                            </Link>
                        }
                    />
                )}
                <Row
                    label="Withheld by clients"
                    value={
                        <span className="font-extrabold text-success">
                            {quarter.quarter_withheld > 0
                                ? minusPeso(quarter.quarter_withheld)
                                : peso(0)}
                        </span>
                    }
                />
            </dl>
        </SectionCard>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3 py-3.5 first:border-t first:border-divider">
            <dt className="text-[15px] font-bold">{label}</dt>
            <dd className="text-right text-[15px]">{value}</dd>
        </div>
    );
}
