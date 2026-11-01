import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { Brand } from '@/components/brand-mark';
import { FieldError } from '@/components/field-error';
import { ClassificationList } from '@/components/profile/classification-list';
import {
    AboutStep,
    CertificateStep,
    EstimateStep,
    ExpensesStep,
    IncomeStep,
    JobStep,
    MethodStep,
    WithholdingStep,
    WorkStep,
} from '@/components/setup/steps';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { complete, show, update } from '@/routes/setup';
import type {
    Comparison,
    SetupAnswers,
    SetupStep,
    SetupStepKey,
    TaxProfileSummary,
} from '@/types';

type Props = {
    step: SetupStepKey;
    previous: SetupStepKey | null;
    steps: SetupStep[];
    answers: SetupAnswers;
    year: number;
    comparison: Comparison | null;
    methodLockedOn: string | null;
    profile: TaxProfileSummary | null;
};

const timeLeft: Record<SetupStepKey, string> = {
    income: 'About 2 minutes left',
    work: 'About 2 minutes left',
    job: 'About 2 minutes left',
    about: 'About 90 seconds left',
    certificate: 'About 1 minute left',
    estimate: 'About 45 seconds left',
    method: 'Almost done · about 30 seconds left',
    expenses: 'Almost done',
    withholding: 'Last question',
    summary: 'All set',
};

export default function SetupShow({
    step,
    previous,
    steps,
    answers,
    year,
    comparison,
    methodLockedOn,
    profile,
}: Props) {
    const form = useForm<SetupAnswers>(answers);
    const { errors } = usePage().props;
    const index = steps.findIndex((s) => s.key === step);
    const setupError = (errors as Record<string, string | undefined>).setup;

    function next() {
        form.submit(update(step), { preserveScroll: true });
    }

    function continueLabel(): string {
        if (step === 'method') {
            return form.data.regime === 'eight_percent'
                ? 'Choose flat 8% and continue'
                : 'Choose graduated and continue';
        }

        return step === 'withholding' ? 'See my tax profile' : 'Continue';
    }

    return (
        <div className="min-h-dvh bg-background px-4 py-6 sm:px-8 sm:py-8">
            <Head title="Setup" />
            <div className="mx-auto max-w-[1120px]">
                <header className="mb-6 flex items-center justify-between gap-4">
                    <Brand suffix="Setup" />
                    <p className="text-sm font-bold text-muted-foreground">
                        <span className="sr-only">
                            Step {index + 1} of {steps.length}.{' '}
                        </span>
                        <span aria-hidden>
                            Step {index + 1} of {steps.length}
                            <span className="hidden sm:inline">
                                {' '}
                                · {timeLeft[step]}
                            </span>
                        </span>
                    </p>
                </header>

                <div className="grid items-start gap-6 lg:grid-cols-[274px_minmax(0,1fr)]">
                    <nav
                        aria-label="Setup steps"
                        className="hidden rounded-card border bg-card p-4 lg:block"
                    >
                        <h2 className="mb-2 px-2 text-xs font-extrabold tracking-[0.4px] text-slate-700">
                            SETUP
                        </h2>
                        <ol className="flex flex-col gap-1">
                            {steps.map((s, i) => {
                                const current = s.key === step;
                                const reachable = s.answered || i <= index;

                                return (
                                    <li key={s.key}>
                                        <Link
                                            href={show(s.key).url}
                                            aria-current={
                                                current ? 'step' : undefined
                                            }
                                            aria-disabled={!reachable}
                                            onClick={(event) =>
                                                !reachable &&
                                                event.preventDefault()
                                            }
                                            className={cn(
                                                'flex items-center gap-3 rounded-xl px-2.5 py-2.5 text-sm font-semibold',
                                                current
                                                    ? 'bg-accent font-bold text-accent-foreground'
                                                    : 'hover:bg-muted',
                                                !reachable &&
                                                    'pointer-events-none text-slate-500',
                                            )}
                                        >
                                            {s.answered && !current ? (
                                                <Check
                                                    className="size-4 shrink-0 text-success"
                                                    aria-label="Done"
                                                />
                                            ) : (
                                                <span
                                                    className={cn(
                                                        'flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-[11px] font-extrabold',
                                                        current &&
                                                            'bg-primary text-primary-foreground',
                                                    )}
                                                >
                                                    {i + 1}
                                                </span>
                                            )}
                                            {s.summary && !current
                                                ? s.summary
                                                : s.label}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ol>
                    </nav>

                    <main className="flex flex-col rounded-card border bg-card p-6 sm:p-8">
                        {setupError && <FieldError message={setupError} />}

                        {step === 'summary' && profile ? (
                            <>
                                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[32px]">
                                    You're set, {profile.first_name}
                                </h1>
                                <p className="mt-2 mb-6 text-[17px] text-muted-foreground">
                                    Here's how the BIR sees you, worked out from
                                    your answers and your COR.
                                </p>
                                <ClassificationList profile={profile} />
                            </>
                        ) : (
                            <form
                                id="setup-step"
                                noValidate
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    next();
                                }}
                            >
                                {step === 'income' && (
                                    <IncomeStep form={form} />
                                )}
                                {step === 'work' && <WorkStep form={form} />}
                                {step === 'job' && <JobStep form={form} />}
                                {step === 'about' && <AboutStep form={form} />}
                                {step === 'certificate' && (
                                    <CertificateStep form={form} />
                                )}
                                {step === 'estimate' && (
                                    <EstimateStep form={form} year={year} />
                                )}
                                {step === 'method' && comparison && (
                                    <MethodStep
                                        form={form}
                                        comparison={comparison}
                                        methodLockedOn={methodLockedOn}
                                    />
                                )}
                                {step === 'expenses' && comparison && (
                                    <ExpensesStep
                                        form={form}
                                        comparison={comparison}
                                        onExpensesChange={(expenses) =>
                                            router.reload({
                                                data: { expenses },
                                                only: ['comparison'],
                                            })
                                        }
                                    />
                                )}
                                {step === 'withholding' && (
                                    <WithholdingStep form={form} />
                                )}
                            </form>
                        )}

                        <div className="mt-8 flex items-center justify-between gap-3 border-t pt-5">
                            {previous ? (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="h-12 rounded-xl px-5 text-[15px] font-bold"
                                >
                                    <Link href={show(previous).url}>Back</Link>
                                </Button>
                            ) : (
                                <span />
                            )}
                            {step === 'summary' ? (
                                <div className="flex flex-wrap items-center justify-end gap-3">
                                    <Link
                                        href={show('income').url}
                                        className="text-sm font-bold text-primary hover:underline"
                                    >
                                        Something's wrong? Edit answers
                                    </Link>
                                    <Button
                                        className="h-12 rounded-xl px-6 text-[15px] font-bold"
                                        onClick={() =>
                                            router.post(complete().url)
                                        }
                                    >
                                        Go to my dashboard
                                    </Button>
                                </div>
                            ) : (
                                <Button
                                    type="submit"
                                    form="setup-step"
                                    disabled={form.processing}
                                    className="h-12 rounded-xl px-6 text-[15px] font-bold"
                                >
                                    {form.processing
                                        ? 'Saving…'
                                        : continueLabel()}
                                </Button>
                            )}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
