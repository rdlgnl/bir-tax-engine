import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useState } from 'react';
import { AnswerCard } from '@/components/transactions/import/answer-card';
import { ColumnsCard } from '@/components/transactions/import/columns-card';
import { ImportTable } from '@/components/transactions/import/import-table';
import { OtherThings } from '@/components/transactions/import/other-things';
import { StatementBar } from '@/components/transactions/import/statement-bar';
import { SummaryTiles } from '@/components/transactions/import/summary-tiles';
import type {
    Answer,
    Row,
    Statement,
    Summary,
} from '@/components/transactions/import/types';
import { UploadCard } from '@/components/transactions/import/upload-card';
import { Button } from '@/components/ui/button';
import { plural } from '@/lib/format';
import { index } from '@/routes/transactions';
import { cancel, confirm } from '@/routes/transactions/import';

type Props = {
    statement: Statement | null;
    summary?: Summary;
    rows?: Row[];
};

export default function ImportStatement({
    statement,
    summary,
    rows = [],
}: Props) {
    const [answers, setAnswers] = useState<Record<number, Answer>>({});
    const [changingColumns, setChangingColumns] = useState(false);
    const [importing, setImporting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const toAnswer = rows.filter((row) => row.status === 'answer');
    const answeredPayments = Object.values(answers).filter(
        (a) => a.kind === 'payment' && a.client.trim() !== '',
    ).length;
    const importCount = (summary?.payments ?? 0) + answeredPayments;

    function answer(row: Row, next: Partial<Answer>) {
        setAnswers((current) => ({
            ...current,
            [row.index]: {
                kind: next.kind ?? current[row.index]?.kind ?? 'payment',
                client: next.client ?? current[row.index]?.client ?? '',
            },
        }));
    }

    function runImport() {
        setImporting(true);
        router.post(
            confirm().url,
            { answers },
            {
                onError: (e) => setErrors(e),
                onFinish: () => setImporting(false),
            },
        );
    }

    return (
        <>
            <Head title="Import payments" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={index().url}
                    aria-label="Back to Transactions"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Import payments
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Bank, e-wallet or platform payout statement (CSV) · read
                        on this computer
                    </p>
                </div>
            </header>

            <div className="flex flex-col gap-5">
                {statement === null ? (
                    <UploadCard />
                ) : (
                    <>
                        <StatementBar
                            statement={statement}
                            onChangeColumns={() =>
                                setChangingColumns(!changingColumns)
                            }
                        />

                        {(!statement.matched || changingColumns) && (
                            <ColumnsCard
                                columns={statement.columns}
                                mapping={statement.mapping}
                                onDone={() => setChangingColumns(false)}
                            />
                        )}

                        {statement.matched && summary && (
                            <>
                                <SummaryTiles summary={summary} />

                                {toAnswer.map((row) => (
                                    <AnswerCard
                                        key={row.index}
                                        row={row}
                                        answer={answers[row.index]}
                                        error={
                                            errors[
                                                `answers.${row.index}.client`
                                            ]
                                        }
                                        onAnswer={(next) => answer(row, next)}
                                    />
                                ))}

                                <ImportTable rows={rows} answers={answers} />

                                <div className="flex flex-wrap justify-end gap-2.5">
                                    <Button
                                        variant="outline"
                                        className="h-12 rounded-xl px-6 text-[15px] font-bold"
                                        onClick={() =>
                                            router.delete(cancel().url)
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        disabled={
                                            importCount === 0 || importing
                                        }
                                        onClick={runImport}
                                        className="h-12 rounded-xl px-6 text-[15px] font-bold"
                                    >
                                        {importing
                                            ? 'Importing…'
                                            : `Import ${plural(importCount, 'payment')}`}
                                    </Button>
                                </div>
                            </>
                        )}
                    </>
                )}

                <OtherThings />
            </div>
        </>
    );
}
