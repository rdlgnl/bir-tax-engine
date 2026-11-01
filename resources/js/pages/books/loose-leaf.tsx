import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Step, StepList } from '@/components/step-list';
import { Button } from '@/components/ui/button';
import { show as books } from '@/routes/books';
import { application } from '@/routes/books/loose-leaf';
import type { BooksType } from '@/types';

type Props = {
    booksType: BooksType;
    books: string[];
    bindWithinDays: number;
    retentionYears: number;
    edition: string;
};

export default function LooseLeaf({
    booksType,
    books: printed,
    bindWithinDays,
    retentionYears,
    edition,
}: Props) {
    const form = application().url;

    return (
        <>
            <Head title="Register loose-leaf books" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={books().url}
                    aria-label="Back to Books of accounts"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Register loose-leaf books
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Print your books from the app instead of copying by hand
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard title="Before you start using them">
                        <StepList>
                            <Step n={1} title="Apply for a Permit to Use">
                                Through ORUS or your RDO; we fill the
                                application (BIR Form 1900) from your profile
                            </Step>
                            <Step n={2} title="Register the books in ORUS">
                                You get a QR stamp for the first page of each
                                volume
                            </Step>
                            <Step n={3} title="Keep the permit and QR stamp">
                                Keep copies with your records
                            </Step>
                        </StepList>
                    </SectionCard>

                    <SectionCard title="Every year">
                        <StepList>
                            <Step n={1} done title="Print monthly books">
                                The cash journals, general journal and ledger,
                                page-numbered,{' '}
                                <Link
                                    href={books().url}
                                    className="font-bold text-primary hover:underline"
                                >
                                    from the Books screen
                                </Link>
                            </Step>
                            <Step
                                n={2}
                                title={`Bind within ${bindWithinDays} days after year end`}
                            >
                                The cash receipts and cash disbursements
                                journals, with the general journal and ledger
                            </Step>
                            <Step
                                n={3}
                                title={`Keep for ${retentionYears} years`}
                            >
                                After the related return is filed (EOPT)
                            </Step>
                        </StepList>
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    {booksType === 'manual' && (
                        <Note title="Manual books are simpler">
                            If you already bought BIR-stamped books from an
                            accredited printer, just keep copying from the Books
                            screen. No permit needed.
                        </Note>
                    )}
                    <Note tone="warning" title="Check with your RDO">
                        Requirements for loose-leaf permits vary in practice.
                        Bring a sample printout when you apply. The application
                        lists the books BuwisBuddy prints (
                        {printed.map((book) => book.toLowerCase()).join(', ')});
                        add any others you'll print.
                    </Note>

                    <DownloadCard
                        href={form}
                        title="Download filled application"
                        detail={`BIR Form 1900 (${edition}), Permit to Use loose-leaf books, from your profile.`}
                    />

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={books().url}>Back</Link>
                        </Button>
                        <Button
                            asChild
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <a href={form} download>
                                Start application
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
