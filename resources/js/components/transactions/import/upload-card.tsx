import { useForm } from '@inertiajs/react';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { upload } from '@/routes/transactions/import';

/** Choosing the statement CSV to read. */
export function UploadCard() {
    const form = useForm<{ statement: File | null }>({ statement: null });

    return (
        <SectionCard title="Choose a statement">
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(upload());
                }}
                className="flex max-w-lg flex-col gap-3"
            >
                <p className="text-[15px] text-muted-foreground">
                    Download your statement as a CSV from your bank, e-wallet or
                    payout platform. We'll match payments to your clients, set
                    aside bills and transfers, and skip what you already
                    recorded. Nothing is uploaded anywhere.
                </p>
                <Input
                    type="file"
                    accept=".csv,text/csv,.txt"
                    aria-label="Statement CSV"
                    onChange={(event) =>
                        form.setData(
                            'statement',
                            event.target.files?.[0] ?? null,
                        )
                    }
                    className="h-11 rounded-xl bg-card py-2"
                />
                <FieldError message={form.errors.statement} />
                <Button
                    type="submit"
                    disabled={!form.data.statement || form.processing}
                    className="h-11 self-start rounded-xl px-5 font-bold"
                >
                    {form.processing ? 'Reading…' : 'Read statement'}
                </Button>
            </form>
        </SectionCard>
    );
}
