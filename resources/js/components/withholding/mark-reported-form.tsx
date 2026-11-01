import { useForm } from '@inertiajs/react';
import { FieldError } from '@/components/field-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { todayIso } from '@/lib/format';
import { store } from '@/routes/filings';

/**
 * Marks an information return filed, such as the 1604-E: no payment, just
 * the date and the confirmation number.
 */
export function MarkReportedForm({
    year,
    form: formName,
}: {
    year: number;
    form: string;
}) {
    const form = useForm({
        year,
        // An annual return is kept as the fourth quarter's.
        quarter: 4,
        form: formName,
        filed_on: todayIso(),
        reference_number: '',
        paid: false,
    });

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(store(), { preserveScroll: true });
            }}
            className="flex flex-col gap-4"
        >
            <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="filed_on" className="font-bold">
                        Date filed
                    </Label>
                    <Input
                        id="filed_on"
                        type="date"
                        max={todayIso()}
                        value={form.data.filed_on}
                        onChange={(event) =>
                            form.setData('filed_on', event.target.value)
                        }
                        className="h-11 rounded-xl"
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="reference_number" className="font-bold">
                        Reference no.
                    </Label>
                    <Input
                        id="reference_number"
                        placeholder="Optional"
                        value={form.data.reference_number}
                        onChange={(event) =>
                            form.setData('reference_number', event.target.value)
                        }
                        className="h-11 rounded-xl"
                    />
                </div>
            </div>
            <FieldError
                message={
                    form.errors.filed_on ??
                    form.errors.reference_number ??
                    form.errors.form
                }
            />
            <Button
                type="submit"
                disabled={form.processing}
                className="h-12 rounded-xl text-base font-bold"
            >
                Mark {formName} filed
            </Button>
        </form>
    );
}
