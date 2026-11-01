import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { home } from '@/routes';
import { destroy, save, show } from '@/routes/corporation';
import type { Corporation } from '@/types';

type CorporationForm = {
    name: string;
    tin: string;
    rdo_code: string;
    registered_address: string;
    zip_code: string;
    contact_number: string;
    email: string;
    operations_began: string;
    total_assets: string;
    deduction_method: 'itemized' | 'osd';
    vat_registered: boolean;
    withholding_agent: boolean;
    employees: string;
};

type TextField = Exclude<
    keyof CorporationForm,
    'deduction_method' | 'vat_registered' | 'withholding_agent' | 'total_assets'
>;

export default function CorporationEdit({
    corporation,
}: {
    corporation: Corporation | null;
}) {
    const form = useForm<CorporationForm>({
        name: corporation?.name ?? '',
        tin: corporation?.tin ?? '',
        rdo_code: corporation?.rdo_code ?? '',
        registered_address: corporation?.registered_address ?? '',
        zip_code: corporation?.zip_code ?? '',
        contact_number: corporation?.contact_number ?? '',
        email: corporation?.email ?? '',
        operations_began: String(
            corporation?.operations_began ?? new Date().getFullYear(),
        ),
        total_assets: corporation ? String(corporation.total_assets) : '',
        deduction_method: corporation?.deduction_method ?? 'itemized',
        vat_registered: corporation?.vat_registered ?? false,
        withholding_agent: corporation?.withholding_agent ?? false,
        employees: String(corporation?.employees ?? 0),
    });
    const field = (
        name: TextField,
        label: string,
        props: React.ComponentProps<typeof Input> = {},
    ) => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={name} className="font-bold">
                {label}
            </Label>
            <Input
                id={name}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                className="h-11 rounded-xl"
                {...props}
            />
            <FieldError message={form.errors[name]} />
        </div>
    );
    const flag = (
        name: 'vat_registered' | 'withholding_agent',
        label: string,
    ) => (
        <label className="flex items-center gap-3 text-[15px]">
            <Checkbox
                checked={form.data[name]}
                onCheckedChange={(c) => form.setData(name, c === true)}
            />
            {label}
        </label>
    );

    return (
        <>
            <Head title="Your corporation" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={corporation ? show().url : home().url}
                    aria-label="Back"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        {corporation
                            ? corporation.name
                            : 'A corporation you run'}
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Its own taxpayer, with its own TIN and returns
                    </p>
                </div>
            </header>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(save());
                }}
                className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <div className="flex flex-col gap-5">
                    <SectionCard title="Registration (from its COR)">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                {field('name', 'Registered name')}
                            </div>
                            {field('tin', 'TIN', {
                                placeholder: '000-000-000',
                                inputMode: 'numeric',
                            })}
                            {field('rdo_code', 'RDO code', {
                                placeholder: '044',
                                inputMode: 'numeric',
                                maxLength: 3,
                            })}
                            <div className="sm:col-span-2">
                                {field(
                                    'registered_address',
                                    'Registered address',
                                )}
                            </div>
                            {field('zip_code', 'ZIP code', {
                                inputMode: 'numeric',
                                maxLength: 4,
                            })}
                            {field('contact_number', 'Contact number')}
                            <div className="sm:col-span-2">
                                {field('email', 'Email address', {
                                    type: 'email',
                                })}
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard title="For its income tax">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {field(
                                'operations_began',
                                'Year operations began',
                                { type: 'number', inputMode: 'numeric' },
                            )}
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="total_assets"
                                    className="font-bold"
                                >
                                    Total assets, excluding land
                                </Label>
                                <MoneyInput
                                    id="total_assets"
                                    value={form.data.total_assets}
                                    onValueChange={(value) =>
                                        form.setData('total_assets', value)
                                    }
                                />
                                <FieldError
                                    message={form.errors.total_assets}
                                />
                            </div>
                            <div className="flex flex-col gap-1.5 sm:col-span-2">
                                <span className="text-sm font-bold">
                                    Deductions
                                </span>
                                <ChoiceChips
                                    label="Deductions"
                                    value={form.data.deduction_method}
                                    onChange={(value) =>
                                        form.setData('deduction_method', value)
                                    }
                                    options={[
                                        {
                                            value: 'itemized',
                                            label: 'Itemized (from the books)',
                                        },
                                        {
                                            value: 'osd',
                                            label: 'Optional standard (40% of gross income)',
                                        },
                                    ]}
                                />
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard title="Its other returns">
                        <div className="flex flex-col gap-3">
                            {flag('vat_registered', 'VAT-registered (2550Q)')}
                            {flag(
                                'withholding_agent',
                                'Withholds tax on its suppliers (0619-E, 1601-EQ)',
                            )}
                            <div className="max-w-[200px]">
                                {field('employees', 'Employees (1601-C)', {
                                    type: 'number',
                                    inputMode: 'numeric',
                                    min: 0,
                                })}
                            </div>
                        </div>
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <Note title="How this works">
                        The corporation keeps its own books. Each quarter, enter
                        its figures from them; the app works out the 1702Q,
                        compares the regular rate with the minimum corporate
                        income tax, and fills the BIR's form.
                    </Note>
                    <Note tone="warning" title="Its accountant signs off">
                        A corporation's returns are signed by its president and
                        treasurer, and its annual return needs audited financial
                        statements.
                    </Note>
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        Save corporation
                    </Button>
                    {corporation && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="text-destructive"
                            onClick={() => {
                                if (
                                    window.confirm(
                                        `Remove ${corporation.name} and its quarterly figures?`,
                                    )
                                ) {
                                    router.delete(destroy().url);
                                }
                            }}
                        >
                            Remove corporation
                        </Button>
                    )}
                </div>
            </form>
        </>
    );
}
