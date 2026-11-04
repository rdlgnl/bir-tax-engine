import { router } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { plural } from '@/lib/format';
import { cancel } from '@/routes/transactions/import';
import { describeMapping } from './columns-card';
import type { Statement } from './types';

/** The statement being read: its name, lines, columns, and a way out. */
export function StatementBar({
    statement,
    onChangeColumns,
}: {
    statement: Statement;
    onChangeColumns: () => void;
}) {
    return (
        <section className="flex flex-wrap items-center gap-4 rounded-card border bg-card p-5">
            <FileText className="size-6 shrink-0 text-primary" aria-hidden />
            <div className="min-w-0 flex-1">
                <p className="truncate text-[17px] font-bold">
                    {statement.name}
                </p>
                <p className="text-sm text-muted-foreground">
                    {plural(statement.lines, 'line')} read
                    {statement.matched &&
                        ` · columns matched: ${describeMapping(statement.columns, statement.mapping)}`}
                    {statement.matched && (
                        <>
                            {' · '}
                            <button
                                type="button"
                                onClick={onChangeColumns}
                                className="font-bold text-primary hover:underline"
                            >
                                Change columns
                            </button>
                        </>
                    )}
                </p>
            </div>
            <Button
                variant="outline"
                className="rounded-xl font-bold"
                onClick={() => router.delete(cancel().url)}
            >
                Choose another file
            </Button>
        </section>
    );
}
