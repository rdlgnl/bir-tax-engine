import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Download, Info, Printer } from 'lucide-react';
import { useState } from 'react';
import { FormSheet } from '@/components/returns/form-sheet';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { formatDate } from '@/lib/format';
import { history } from '@/routes';
import { pdf, show } from '@/routes/returns';
import { show as setupShow } from '@/routes/setup';
import type { ReturnLayout } from '@/types';

type Props = {
    layout: ReturnLayout;
    filedOn: string | null;
    missingDetails: { label: string; step: 'about' | 'certificate' }[];
};

export default function ReturnPreview({
    layout,
    filedOn,
    missingDetails,
}: Props) {
    const args: [number, number, string] = [
        layout.year,
        layout.quarter,
        layout.form,
    ];
    const [checks, setChecks] = useState({ identity: false, income: false });
    // Only an unfiled return picks up profile changes.
    const missing = filedOn ? [] : missingDetails;
    const back = filedOn
        ? history({
              query: {
                  year: layout.year,
                  quarter: layout.quarter,
                  form: layout.form,
              },
          }).url
        : show(args).url;

    return (
        <>
            <Head title={`Filled ${layout.form}`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={back}
                        aria-label="Back"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled {layout.form}
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            On the BIR's own form · {layout.period}
                            {filedOn
                                ? ` · as filed ${formatDate(filedOn, 'long')}`
                                : ' · from your records on this computer'}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2.5">
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-xl font-bold"
                    >
                        <a
                            href={pdf(args, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(args).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <FormSheet layout={layout} />

                <div className="flex flex-col gap-5">
                    <SectionCard>
                        <dl className="divide-y divide-divider text-sm">
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Form version
                                </dt>
                                <dd className="text-right font-bold">
                                    {layout.form} · {layout.edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Fields filled
                                </dt>
                                <dd className="text-right font-bold">
                                    {missing.length === 0
                                        ? 'All but your signature'
                                        : 'All but your signature and the details below'}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Rule pack
                                </dt>
                                <dd className="text-right font-bold">
                                    {layout.rule_pack}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {!filedOn && (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Check before you sign
                            </h2>
                            <label className="flex items-center gap-3 py-1.5 text-[15px]">
                                <Checkbox
                                    checked={checks.identity}
                                    onCheckedChange={(c) =>
                                        setChecks({
                                            ...checks,
                                            identity: c === true,
                                        })
                                    }
                                />
                                My name, TIN and RDO are right
                            </label>
                            <label className="flex items-center gap-3 py-1.5 text-[15px]">
                                <Checkbox
                                    checked={checks.income}
                                    onCheckedChange={(c) =>
                                        setChecks({
                                            ...checks,
                                            income: c === true,
                                        })
                                    }
                                />
                                All my{' '}
                                {layout.quarter === 4
                                    ? layout.year
                                    : `Q${layout.quarter}`}{' '}
                                income is recorded
                            </label>
                            {layout.form === '2551Q' && (
                                <p className="mt-2 text-[13px] text-muted-foreground">
                                    Sales count by invoice date, not when you
                                    were paid.
                                </p>
                            )}
                        </SectionCard>
                    )}

                    {missing.length > 0 && (
                        <SectionCard>
                            <h2 className="mb-1.5 text-lg font-extrabold">
                                Not on the form yet
                            </h2>
                            <p className="text-[15px]">
                                Your{' '}
                                {missing
                                    .map((detail) => detail.label)
                                    .join(', ')}{' '}
                                will print blank. Write them in, or{' '}
                                <Link
                                    href={
                                        setupShow(
                                            missing.some(
                                                (detail) =>
                                                    detail.step === 'about',
                                            )
                                                ? 'about'
                                                : 'certificate',
                                        ).url
                                    }
                                    className="font-bold text-primary underline-offset-4 hover:underline"
                                >
                                    add them to your profile
                                </Link>
                                .
                            </p>
                        </SectionCard>
                    )}

                    <p className="flex items-start gap-2.5 rounded-2xl border border-[#C9D7FB] bg-selected px-4 py-3.5 text-sm text-accent-foreground">
                        <Info className="mt-0.5 size-4 shrink-0" aria-hidden />
                        The download is the BIR's own {layout.form}, filled in.
                        Print it at actual size on long bond paper (8.5 × 13
                        in), sign it, and file it with your payment at a bank
                        the BIR accredits, or copy the values into eBIRForms.
                    </p>

                    {!filedOn && (
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
                        >
                            <Link href={show(args).url}>
                                I've filed it · mark as filed
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
