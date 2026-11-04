import { useHttp } from '@inertiajs/react';
import type { InertiaFormProps } from '@inertiajs/react';
import { useState } from 'react';
import { read } from '@/routes/certificates';
import { UNREADABLE } from './types';
import type { CertificateForm, ReadField, ReadKey, Reading } from './types';

/**
 * Reads a chosen 2307 on this computer and fills the form; anything the
 * reader isn't sure of is marked to check, until the taxpayer changes it.
 */
export function useCertificateReading(form: InertiaFormProps<CertificateForm>) {
    const reader = useHttp<{ file: File | null }, Reading>({ file: null });
    const [reading, setReading] = useState<Reading | null>(null);
    const [toCheck, setToCheck] = useState<ReadKey[]>([]);
    const found = reading?.readable
        ? (
              [
                  'payor_name',
                  'payor_tin',
                  'year',
                  'amount_paid',
                  'tax_withheld',
                  'atc_code',
              ] as const
          ).filter((key) => reading.fields[key].value !== null).length
        : 0;

    function choose(file: File | null) {
        form.setData('file', file);
        setReading(null);
        setToCheck([]);

        if (!file) {
            return;
        }

        reader.transform(() => ({ file }));
        reader
            .submit(read())
            .then((result) => {
                setReading(result);

                if (!result.readable) {
                    return;
                }

                const f = result.fields;
                const period =
                    f.year.value !== null && f.quarter.value !== null;
                form.setData((data) => ({
                    ...data,
                    payor_name: f.payor_name.value ?? data.payor_name,
                    payor_tin: f.payor_tin.value ?? data.payor_tin,
                    year: period ? (f.year.value as number) : data.year,
                    quarter: period
                        ? (f.quarter.value as number)
                        : data.quarter,
                    amount_paid:
                        f.amount_paid.value !== null
                            ? String(f.amount_paid.value)
                            : data.amount_paid,
                    tax_withheld:
                        f.tax_withheld.value !== null
                            ? String(f.tax_withheld.value)
                            : data.tax_withheld,
                    atc_code: f.atc_code.value ?? data.atc_code,
                }));
                setToCheck(
                    (
                        [
                            ['payor_name', f.payor_name],
                            ['payor_tin', f.payor_tin],
                            ['period', f.quarter],
                            ['amount_paid', f.amount_paid],
                            ['tax_withheld', f.tax_withheld],
                            ['atc_code', f.atc_code],
                        ] as [ReadKey, ReadField<unknown>][]
                    )
                        .filter(
                            ([, field]) => field.value !== null && !field.sure,
                        )
                        .map(([key]) => key),
                );
            })
            // A file that can't be read is typed in, like a photo.
            .catch(() => setReading(UNREADABLE));
    }

    /** Once the taxpayer changes a field, it's theirs: no longer to check. */
    function checked(key: ReadKey) {
        setToCheck((keys) => keys.filter((k) => k !== key));
    }

    return {
        reading,
        toCheck,
        found,
        processing: reader.processing,
        choose,
        checked,
    };
}
