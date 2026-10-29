import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, Lock } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Slider } from '@/components/ui/slider';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { compare, show } from '@/routes/tax';
import type { CompareOption, CompareOptionKey, Comparison } from '@/types';

type Props = {
    comparison: Comparison;
    hasSalaryInProfile: boolean;
    regime: string | null;
    year: number;
    methodLockedOn: string | null;
    nextDecisionDue: string;
};

type Scenario = 'high_income' | 'high_expenses' | 'job';

const titles: Record<CompareOptionKey, string> = {
    eight_percent: 'Flat 8%',
    graduated_osd: 'Graduated + flat 40% deduction',
    graduated_itemized: 'Graduated + actual expenses',
};

export default function TaxCompare({
    comparison,
    hasSalaryInProfile,
    regime,
    year,
    methodLockedOn,
    nextDecisionDue,
}: Props) {
    const [income, setIncome] = useState(comparison.income);
    const [expenses, setExpenses] = useState(comparison.expense_percent);
    const [hasSalary, setHasSalary] = useState(
        comparison.salary !== null || hasSalaryInProfile,
    );
    const [salary, setSalary] = useState(String(comparison.salary ?? 400_000));
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(
                compare().url,
                {
                    income,
                    expenses,
                    salary: hasSalary ? Number(salary || 0) : undefined,
                },
                {
                    only: ['comparison'],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 250);

        return () => clearTimeout(timer);
    }, [income, expenses, hasSalary, salary]);

    const max = Math.max(...comparison.options.map((o) => o.total), 1);
    const best = comparison.options.find((o) => o.key === comparison.best);
    const osd = comparison.options.find((o) => o.key === 'graduated_osd')!;

    function tryScenario(scenario: Scenario) {
        if (scenario === 'high_income') setIncome(3_200_000);
        if (scenario === 'high_expenses') setExpenses(80);
        if (scenario === 'job') setHasSalary(true);
    }

    const activeScenario: Scenario | null =
        income >= 3_200_000
            ? 'high_income'
            : expenses >= 80
              ? 'high_expenses'
              : hasSalary
                ? 'job'
                : null;

    return (
        <>
            <Head title="What if?" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={show().url}
                    aria-label="Back to My tax"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        What if?
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Compare ways to compute your tax. Every figure comes
                        from the tax engine.
                    </p>
                </div>
            </header>

            <p className="mb-5 flex items-start gap-2.5 rounded-xl border border-warning-border bg-warning-soft px-4 py-3 text-sm text-warning">
                <Lock className="mt-0.5 size-4 shrink-0" aria-hidden />
                {methodLockedOn
                    ? `${year} is locked to ${regime === 'eight_percent' ? '8%' : 'graduated rates'} (Q1 filed ${formatDate(methodLockedOn)}). Use this to plan ${year + 1}, decided with your Q1 ${year + 1} return due ${formatDate(nextDecisionDue, 'short')}.`
                    : `You can still switch for ${year} until you file your Q1 return. After that it locks for the year.`}
            </p>

            <div className="grid items-start gap-5 lg:grid-cols-[370px_minmax(0,1fr)]">
                <SectionCard className="flex flex-col gap-6">
                    <div className="flex flex-col gap-3">
                        <div className="flex items-baseline justify-between">
                            <Label
                                id="income-label"
                                className="text-[15px] font-bold"
                            >
                                Yearly income
                            </Label>
                            <span className="text-[22px] font-extrabold">
                                {peso(income)}
                            </span>
                        </div>
                        <Slider
                            aria-labelledby="income-label"
                            min={0}
                            max={4_000_000}
                            step={10_000}
                            value={[income]}
                            onValueChange={([value]) => setIncome(value)}
                        />
                    </div>
                    <div className="flex flex-col gap-3">
                        <div className="flex items-baseline justify-between">
                            <Label
                                id="expenses-label"
                                className="text-[15px] font-bold"
                            >
                                Expenses with valid invoices
                            </Label>
                            <span className="text-[22px] font-extrabold">
                                {expenses}%
                            </span>
                        </div>
                        <Slider
                            aria-labelledby="expenses-label"
                            min={0}
                            max={95}
                            step={5}
                            value={[expenses]}
                            onValueChange={([value]) => setExpenses(value)}
                        />
                    </div>
                    <div className="flex flex-col gap-2.5">
                        <label className="flex items-center gap-3 text-[15px] font-bold">
                            <Checkbox
                                checked={hasSalary}
                                onCheckedChange={(checked) =>
                                    setHasSalary(checked === true)
                                }
                            />
                            I'll also have salary from a job
                        </label>
                        {hasSalary && (
                            <div className="flex flex-col gap-1.5 pl-7">
                                <Label htmlFor="salary" className="text-sm">
                                    Yearly taxable salary
                                </Label>
                                <MoneyInput
                                    id="salary"
                                    value={salary}
                                    onValueChange={setSalary}
                                />
                            </div>
                        )}
                    </div>
                    <div className="flex flex-col gap-2.5">
                        <span className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700">
                            TRY A SCENARIO
                        </span>
                        <ChoiceChips<Scenario>
                            label="Try a scenario"
                            value={activeScenario}
                            onChange={tryScenario}
                            options={[
                                { value: 'high_income', label: 'I earn ₱3.2M' },
                                {
                                    value: 'high_expenses',
                                    label: 'Expenses hit 80%',
                                },
                                { value: 'job', label: 'I take a job too' },
                            ]}
                        />
                    </div>
                </SectionCard>

                <SectionCard
                    title="Total tax for the year"
                    className="flex flex-col"
                >
                    <ul className="flex flex-col gap-5">
                        {comparison.options.map((option) => (
                            <OptionBar
                                key={option.key}
                                option={option}
                                max={max}
                                best={option.key === comparison.best}
                            />
                        ))}
                    </ul>

                    {best && comparison.savings > 0 && (
                        <p className="mt-5 border-t border-divider pt-4 text-[15px]">
                            {titles[best.key]} saves you{' '}
                            <strong>{peso(comparison.savings)}</strong> over the
                            next best option at these numbers.
                        </p>
                    )}

                    {comparison.over_limit && (
                        <div className="mt-4 rounded-2xl bg-muted p-4">
                            <p className="font-extrabold">
                                Scenario: you earn {peso(comparison.income)}
                            </p>
                            <p className="mt-1 text-[15px] text-slate-700">
                                The 8% option ends for the whole year and you
                                must register for VAT. With the flat 40%
                                deduction, income tax would be about{' '}
                                <strong>{peso(osd.income_tax)}</strong>, plus
                                12% VAT on sales after registration.
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                RR 8-2018 Sec. 3(D) · NIRC Sec. 109(BB)
                            </p>
                        </div>
                    )}
                </SectionCard>
            </div>
        </>
    );
}

function OptionBar({
    option,
    max,
    best,
}: {
    option: CompareOption;
    max: number;
    best: boolean;
}) {
    const detail =
        option.key === 'eight_percent'
            ? option.exempt_amount > 0
                ? `(${peso(option.taxable + option.exempt_amount)} − ${peso(option.exempt_amount)}) × 8% · no percentage tax`
                : `${peso(option.taxable)} × 8% · no ₱250,000 reduction with a salary`
            : `Income tax ${peso(option.income_tax)} on ${peso(option.taxable)}${option.percentage_tax > 0 ? ` + percentage tax ${peso(option.percentage_tax)}` : ''}`;

    return (
        <li className={cn(!option.available && 'opacity-60')}>
            <div className="flex items-baseline justify-between gap-3">
                <span className="text-[15px] font-bold">
                    {titles[option.key]}
                </span>
                <span className="text-[22px] font-extrabold">
                    {option.available ? peso(option.total) : 'Not available'}
                </span>
            </div>
            <div
                className="mt-2 h-3.5 overflow-hidden rounded-full bg-divider"
                aria-hidden
            >
                <div
                    className={cn(
                        'h-full rounded-full',
                        best ? 'bg-primary' : 'bg-slate-400',
                    )}
                    style={{
                        width: `${option.available ? (option.total / max) * 100 : 0}%`,
                    }}
                />
            </div>
            <p className="mt-1.5 text-[13px] text-muted-foreground">
                {option.available
                    ? detail
                    : 'Only for sales up to ₱3,000,000 a year.'}
            </p>
        </li>
    );
}
