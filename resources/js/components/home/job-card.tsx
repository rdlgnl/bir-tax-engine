import { Link, useForm } from '@inertiajs/react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { peso } from '@/lib/format';
import { compensation } from '@/routes';
import { job as updateJob } from '@/routes/profile';
import type { HomeJob } from '@/types';

/** "Your job": the salary's tax is the employer's until the annual return. */
export function JobCard({ job, year }: { job: HomeJob; year: number }) {
    const form = useForm({
        employer_name: job.employer ?? '',
        monthly_salary:
            job.monthly_salary === null ? '' : String(job.monthly_salary),
    });

    return (
        <SectionCard as="div">
            <h2 className="mb-3 text-lg font-extrabold">Your job</h2>
            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(updateJob(), { preserveScroll: true });
                }}
            >
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="employer_name" className="font-bold">
                            Employer
                        </Label>
                        <Input
                            id="employer_name"
                            value={form.data.employer_name}
                            onChange={(event) =>
                                form.setData(
                                    'employer_name',
                                    event.target.value,
                                )
                            }
                            className="h-11 rounded-xl"
                        />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="monthly_salary" className="font-bold">
                            Monthly taxable salary
                        </Label>
                        <MoneyInput
                            id="monthly_salary"
                            value={form.data.monthly_salary}
                            onValueChange={(value) =>
                                form.setData('monthly_salary', value)
                            }
                        />
                    </div>
                </div>
                <FieldError
                    message={
                        form.errors.employer_name ?? form.errors.monthly_salary
                    }
                />
                {form.isDirty && (
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="mt-3 h-10 rounded-xl font-bold"
                    >
                        Save
                    </Button>
                )}
            </form>

            {job.annual_salary !== null && (
                <dl className="mt-3 text-[15px] tabular-nums">
                    <div className="flex justify-between border-t border-divider py-3">
                        <dt>Taxable salary, {year} (est.)</dt>
                        <dd className="font-bold">{peso(job.annual_salary)}</dd>
                    </div>
                    <div className="flex justify-between border-t border-divider py-3">
                        <dt>Tax your employer withholds</dt>
                        <dd className="font-bold">
                            ≈ {peso(job.employer_withholds ?? 0)}
                        </dd>
                    </div>
                </dl>
            )}
            <p className="text-[13px] text-muted-foreground">
                Nothing to pay on your salary now. It's taxed at graduated rates
                and reconciled in your annual return.
            </p>
            <Button
                asChild
                variant="outline"
                className="mt-4 h-11 rounded-xl font-bold"
            >
                <Link href={compensation(year).url}>
                    {job.has_2316
                        ? `Your ${year} 2316`
                        : 'Upload 2316 (by Jan 31)'}
                </Link>
            </Button>
        </SectionCard>
    );
}
