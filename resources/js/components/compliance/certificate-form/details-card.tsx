import type { InertiaFormProps } from '@inertiajs/react';
import { useId } from 'react';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { quarterMonths } from '@/lib/format';
import { Field } from './field';
import type { CertificateForm, ReadKey } from './types';

/** What the 2307 says: typed in, or read from the file and checked. */
export function DetailsCard({
    form,
    clients,
    periods,
    toCheck,
    onChecked,
}: {
    form: InertiaFormProps<CertificateForm>;
    clients: string[];
    periods: { year: number; quarter: number }[];
    toCheck: ReadKey[];
    onChecked: (key: ReadKey) => void;
}) {
    const clientListId = useId();
    const { year, quarter } = form.data;

    return (
        <SectionCard className="flex flex-col gap-4">
            <Field
                label="Client (payor)"
                htmlFor="payor_name"
                error={form.errors.payor_name}
                check={toCheck.includes('payor_name')}
            >
                <Input
                    id="payor_name"
                    list={clientListId}
                    autoComplete="off"
                    value={form.data.payor_name}
                    onChange={(event) => {
                        form.setData('payor_name', event.target.value);
                        onChecked('payor_name');
                    }}
                    className="h-11 rounded-xl"
                />
                <datalist id={clientListId}>
                    {clients.map((client) => (
                        <option key={client} value={client} />
                    ))}
                </datalist>
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field
                    label="Client TIN"
                    htmlFor="payor_tin"
                    error={form.errors.payor_tin}
                    optional
                    check={toCheck.includes('payor_tin')}
                >
                    <Input
                        id="payor_tin"
                        inputMode="numeric"
                        placeholder="008-123-456-000"
                        value={form.data.payor_tin}
                        onChange={(event) => {
                            form.setData('payor_tin', event.target.value);
                            onChecked('payor_tin');
                        }}
                        className="h-11 rounded-xl"
                    />
                </Field>
                <Field
                    label="Period"
                    htmlFor="period"
                    error={form.errors.quarter ?? form.errors.year}
                    check={toCheck.includes('period')}
                >
                    <Select
                        value={`${year}-${quarter}`}
                        onValueChange={(value) => {
                            const [y, q] = value.split('-').map(Number);
                            form.setData({ ...form.data, year: y, quarter: q });
                            onChecked('period');
                        }}
                    >
                        <SelectTrigger
                            id="period"
                            className="!h-11 w-full rounded-xl"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {periods.map((q) => (
                                <SelectItem
                                    key={`${q.year}-${q.quarter}`}
                                    value={`${q.year}-${q.quarter}`}
                                >
                                    {quarterMonths(q.quarter)} {q.year} (Q
                                    {q.quarter})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>
                <Field
                    label="Amount paid"
                    htmlFor="amount_paid"
                    error={form.errors.amount_paid}
                    check={toCheck.includes('amount_paid')}
                >
                    <MoneyInput
                        id="amount_paid"
                        value={form.data.amount_paid}
                        onValueChange={(value) => {
                            form.setData('amount_paid', value);
                            onChecked('amount_paid');
                        }}
                    />
                </Field>
                <Field
                    label="Tax withheld"
                    htmlFor="tax_withheld"
                    error={form.errors.tax_withheld}
                    check={toCheck.includes('tax_withheld')}
                >
                    <MoneyInput
                        id="tax_withheld"
                        value={form.data.tax_withheld}
                        onValueChange={(value) => {
                            form.setData('tax_withheld', value);
                            onChecked('tax_withheld');
                        }}
                    />
                </Field>
            </div>
            <Field
                label="Tax code (ATC)"
                htmlFor="atc_code"
                error={form.errors.atc_code}
                optional
                check={toCheck.includes('atc_code')}
            >
                <Input
                    id="atc_code"
                    placeholder="WI010"
                    value={form.data.atc_code}
                    onChange={(event) => {
                        form.setData(
                            'atc_code',
                            event.target.value.toUpperCase(),
                        );
                        onChecked('atc_code');
                    }}
                    className="h-11 max-w-48 rounded-xl"
                />
                <p className="text-[13px] text-muted-foreground">
                    Copy it exactly as printed. You won't need to understand it.
                </p>
            </Field>
        </SectionCard>
    );
}
