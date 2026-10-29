import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, plural } from '@/lib/format';
import { history } from '@/routes';

type Props = { nextDeadline: string | null; daysUntilNext: number | null };

export default function NothingDue({ nextDeadline, daysUntilNext }: Props) {
    return (
        <>
            <Head title="File a return" />
            <h1 className="mb-6 text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                File a return
            </h1>
            <SectionCard className="flex max-w-xl flex-col items-start gap-3">
                <CheckCircle2 className="size-8 text-success" aria-hidden />
                <p className="text-xl font-extrabold">
                    Nothing to file right now
                </p>
                <p className="text-[15px] text-muted-foreground">
                    {nextDeadline
                        ? `Your next return is due ${formatDate(nextDeadline, 'weekday')}${daysUntilNext !== null ? `, in ${plural(daysUntilNext, 'day')}` : ''}. You can prepare it once its quarter ends.`
                        : 'Every return so far is filed.'}
                </p>
                <Button
                    asChild
                    variant="outline"
                    className="rounded-xl font-bold"
                >
                    <Link href={history().url}>See past returns</Link>
                </Button>
            </SectionCard>
        </>
    );
}
