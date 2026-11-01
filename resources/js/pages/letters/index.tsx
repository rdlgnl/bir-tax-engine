import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Plus } from 'lucide-react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { create, show } from '@/routes/letters';
import type { BirLetter } from '@/types';

export default function Letters({ letters }: { letters: BirLetter[] }) {
    return (
        <>
            <Head title="Letters from the BIR" />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Letters from the BIR
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Kept with your documents on this computer
                    </p>
                </div>
                <Button asChild className="h-11 rounded-xl px-5 font-bold">
                    <Link href={create().url}>
                        <Plus className="size-5" aria-hidden />
                        Add a letter
                    </Link>
                </Button>
            </header>

            <SectionCard>
                {letters.length === 0 ? (
                    <p className="py-2 text-sm text-muted-foreground">
                        No letters yet. When one arrives, add it to see what
                        your records say and what to send.
                    </p>
                ) : (
                    <ul className="divide-y divide-divider">
                        {letters.map((letter) => (
                            <li key={letter.id}>
                                <Link
                                    href={show(letter.id).url}
                                    className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                >
                                    <span>
                                        <span className="block text-[15px] font-bold">
                                            {letter.kind_label}
                                            {letter.form &&
                                                ` · ${letter.form}, ${letter.period}`}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {letter.resolved_on
                                                ? `Dealt with ${formatDate(letter.resolved_on)}`
                                                : letter.reply_by
                                                  ? `Reply by ${formatDate(letter.reply_by, 'long')}`
                                                  : letter.dated_on
                                                    ? `Dated ${formatDate(letter.dated_on, 'long')}`
                                                    : 'Open'}
                                        </span>
                                    </span>
                                    <ChevronRight
                                        className="size-4 text-muted-foreground"
                                        aria-hidden
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </SectionCard>
        </>
    );
}
