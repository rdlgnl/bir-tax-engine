import { Head, Link } from '@inertiajs/react';
import { Bell, FileText, Lock, SquareCheck } from 'lucide-react';
import { Brand } from '@/components/brand-mark';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/setup';

const promises = [
    {
        icon: SquareCheck,
        title: 'We figure out which taxes apply',
        body: '8% or graduated, percentage tax, VAT: from your answers and your COR.',
    },
    {
        icon: FileText,
        title: 'We compute it from your income',
        body: 'Every peso traced back to a BIR rule you can read.',
    },
    {
        icon: Bell,
        title: 'We remind you before deadlines',
        body: 'Weekends and holidays already accounted for.',
    },
];

export default function Welcome({ setupStarted }: { setupStarted: boolean }) {
    return (
        <div className="min-h-dvh bg-card px-4 py-8 sm:px-8 lg:px-40">
            <Head title="Know what you owe the BIR" />
            <div className="mx-auto max-w-[1120px]">
                <header className="mb-14">
                    <Brand />
                </header>

                <main className="grid gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <h1 className="text-[40px] leading-[1.05] font-extrabold tracking-[-1.5px] sm:text-[52px]">
                            Know what you owe the BIR, before it's due.
                        </h1>
                        <p className="mt-6 text-lg text-muted-foreground">
                            For freelancers, professionals and small business
                            owners. Answer a few plain questions; the app
                            handles the BIR rules.
                        </p>
                        <div className="mt-7 flex flex-wrap gap-3">
                            <Button
                                asChild
                                className="h-14 rounded-xl px-7 text-base font-bold"
                            >
                                <Link href={show().url}>
                                    {setupStarted
                                        ? 'Continue setup'
                                        : 'Get started · about 2 minutes'}
                                </Link>
                            </Button>
                        </div>
                        <p className="mt-5 flex items-center gap-2 text-sm text-muted-foreground">
                            <Lock className="size-4" aria-hidden />
                            No account needed. Your data stays on this computer.
                        </p>
                    </div>

                    <ul className="flex flex-col gap-3.5">
                        {promises.map(({ icon: Icon, title, body }) => (
                            <li
                                key={title}
                                className="flex items-start gap-3.5 rounded-2xl bg-muted p-5"
                            >
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary">
                                    <Icon className="size-5" aria-hidden />
                                </span>
                                <span>
                                    <span className="block text-[17px] font-bold">
                                        {title}
                                    </span>
                                    <span className="text-[15px] text-muted-foreground">
                                        {body}
                                    </span>
                                </span>
                            </li>
                        ))}
                    </ul>
                </main>
            </div>
        </div>
    );
}
