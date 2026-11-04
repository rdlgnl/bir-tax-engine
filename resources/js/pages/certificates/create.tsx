import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useMemo } from 'react';
import { DetailsCard } from '@/components/compliance/certificate-form/details-card';
import { FileDrop } from '@/components/compliance/certificate-form/file-drop';
import { MatchBanner } from '@/components/compliance/certificate-form/match-banner';
import type {
    CertificateForm,
    Match,
} from '@/components/compliance/certificate-form/types';
import { useCertificateReading } from '@/components/compliance/certificate-form/use-certificate-reading';
import { Button } from '@/components/ui/button';
import { useServerPreview } from '@/hooks/use-server-preview';
import { match, store } from '@/routes/certificates';
import { show as complianceShow } from '@/routes/compliance';

type Props = {
    clients: string[];
    quarters: { year: number; quarter: number }[];
    prefill: {
        payor_name: string;
        year: number;
        quarter: number;
        amount_paid: number;
        tax_withheld: number;
    } | null;
};

export default function CertificateCreate({
    clients,
    quarters,
    prefill,
}: Props) {
    const form = useForm<CertificateForm>({
        payor_name: prefill?.payor_name ?? '',
        payor_tin: '',
        year: prefill?.year ?? quarters[0].year,
        quarter: prefill?.quarter ?? quarters[0].quarter,
        amount_paid: prefill ? String(prefill.amount_paid) : '',
        tax_withheld: prefill ? String(prefill.tax_withheld) : '',
        atc_code: '',
        file: null,
    });
    const { year, quarter } = form.data;
    const reading = useCertificateReading(form);
    // A certificate read for a quarter outside the list still has to show.
    const periods = useMemo(
        () =>
            quarters.some((q) => q.year === year && q.quarter === quarter)
                ? quarters
                : [{ year, quarter }, ...quarters],
        [quarters, year, quarter],
    );

    const { response: result, loading } = useServerPreview<
        {
            payor_name: string;
            year: number;
            quarter: number;
            tax_withheld: string;
        },
        Match
    >(
        match,
        {
            payor_name: form.data.payor_name,
            year,
            quarter,
            tax_withheld: form.data.tax_withheld || '0',
        },
        form.data.payor_name.trim().length > 1,
    );

    return (
        <>
            <Head title="Add a 2307" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={complianceShow().url}
                    aria-label="Back to Compliance"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Tax certificate (2307)
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Proof a client paid tax for you · kept on this computer
                    </p>
                </div>
            </header>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(store());
                }}
                className="grid items-start gap-5 lg:grid-cols-2"
            >
                <FileDrop
                    file={form.data.file}
                    error={form.errors.file}
                    processing={reading.processing}
                    reading={reading.reading}
                    found={reading.found}
                    toCheck={reading.toCheck.length}
                    onChoose={reading.choose}
                />

                <div className="flex flex-col gap-4">
                    <DetailsCard
                        form={form}
                        clients={clients}
                        periods={periods}
                        toCheck={reading.toCheck}
                        onChecked={reading.checked}
                    />

                    {result && (
                        <MatchBanner
                            result={result}
                            client={form.data.payor_name}
                            quarter={quarter}
                            claimed={Number(form.data.tax_withheld || 0)}
                            loading={loading}
                        />
                    )}

                    <div className="flex justify-end gap-2.5">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl px-5 font-bold"
                        >
                            <Link href={complianceShow().url}>Cancel</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-12 rounded-xl px-6 text-[15px] font-bold"
                        >
                            {form.processing ? 'Saving…' : 'Save certificate'}
                        </Button>
                    </div>
                </div>
            </form>
        </>
    );
}
