import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { data } from '@/routes';
import { clear, feedback, store } from '@/routes/ask';
import { create as lettersCreate } from '@/routes/letters';

type Rule = { id: string; version: number; title: string; basis: string };

type Turn = {
    question: string;
    answer: string;
    error: boolean;
    rules: Rule[];
    figures: { label: string; value: string }[];
    feedback: 'helpful' | 'wrong' | null;
};

type Props = {
    ready: boolean;
    enabled: boolean;
    provider: string;
    turns: Turn[];
    suggestions: string[];
};

function Bubble({
    from,
    tone = 'default',
    children,
}: {
    from: 'you' | 'assistant';
    tone?: 'default' | 'error';
    children: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                'max-w-[85%] rounded-2xl px-4 py-3 text-[15px] leading-relaxed',
                from === 'you'
                    ? 'self-end bg-primary text-primary-foreground'
                    : tone === 'error'
                      ? 'self-start border border-warning-border bg-warning-soft text-warning'
                      : 'self-start bg-muted',
            )}
        >
            {children}
        </div>
    );
}

export default function Ask({
    ready,
    enabled,
    provider,
    turns,
    suggestions,
}: Props) {
    const { errors } = usePage().props;
    const error = (errors as Record<string, string | undefined>).question;
    const [question, setQuestion] = useState('');
    const [pending, setPending] = useState<string | null>(null);
    const end = useRef<HTMLDivElement>(null);
    const lastIndex = turns.length - 1;
    const last = turns[lastIndex];

    useEffect(() => {
        end.current?.scrollIntoView({ block: 'end' });
    }, [turns.length, pending]);

    const send = (text: string) => {
        const asked = text.trim();

        if (asked === '' || pending !== null) {
            return;
        }

        router.post(
            store().url,
            { question: asked },
            {
                preserveScroll: true,
                onStart: () => setPending(asked),
                onSuccess: () => setQuestion(''),
                onFinish: () => setPending(null),
            },
        );
    };

    if (!ready) {
        return (
            <>
                <Head title="Ask" />
                <div className="mx-auto max-w-[640px]">
                    <SectionCard>
                        <h1 className="text-[24px] leading-tight font-extrabold tracking-tight">
                            Ask about your taxes
                        </h1>
                        <p className="mt-2 text-[15px] text-muted-foreground">
                            {enabled
                                ? 'The assistant is on but has no API key yet.'
                                : 'The assistant is off.'}{' '}
                            It answers questions from your own figures, through
                            an AI provider you choose, with your own API key.
                            When it's on, your question and only the numbers
                            needed to answer it go to that provider; your name,
                            TIN and address never do.
                        </p>
                        <Button asChild className="mt-4 rounded-xl font-bold">
                            <Link href={data().url}>
                                {enabled ? 'Add your API key' : 'Turn it on'}
                            </Link>
                        </Button>
                    </SectionCard>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Ask" />

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section className="flex min-h-[70vh] flex-col rounded-card border bg-card">
                    <header className="flex flex-wrap items-start justify-between gap-3 border-b px-6 py-5">
                        <div>
                            <h1 className="text-[24px] leading-tight font-extrabold tracking-tight">
                                Ask about your taxes
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Answers use your numbers from the tax engine and
                                cite the BIR rule. Not legal advice.
                            </p>
                        </div>
                        {turns.length > 0 && (
                            <Button
                                variant="ghost"
                                className="rounded-xl text-sm font-bold"
                                onClick={() =>
                                    router.delete(clear().url, {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                New conversation
                            </Button>
                        )}
                    </header>

                    <div className="flex flex-1 flex-col gap-3 px-6 py-5">
                        {turns.length === 0 && pending === null && (
                            <p className="m-auto max-w-sm text-center text-[15px] text-muted-foreground">
                                Ask why your tax is what it is, what's due next,
                                or what a form is for.
                            </p>
                        )}
                        {turns.map((turn, index) => (
                            <div key={index} className="flex flex-col gap-3">
                                <Bubble from="you">{turn.question}</Bubble>
                                <Bubble
                                    from="assistant"
                                    tone={turn.error ? 'error' : 'default'}
                                >
                                    <p className="whitespace-pre-line">
                                        {turn.answer}
                                    </p>
                                    {turn.rules.length > 0 && (
                                        <div className="mt-3 flex flex-wrap gap-1.5">
                                            {turn.rules.map((rule) => (
                                                <span
                                                    key={rule.id}
                                                    title={`${rule.title} · ${rule.basis}`}
                                                    className="rounded-full bg-selected px-2.5 py-1 text-xs font-bold text-accent-foreground"
                                                >
                                                    Rule {rule.id} v
                                                    {rule.version}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </Bubble>
                            </div>
                        ))}
                        {pending !== null && (
                            <>
                                <Bubble from="you">{pending}</Bubble>
                                <Bubble from="assistant">
                                    <span className="text-muted-foreground">
                                        Looking at your numbers…
                                    </span>
                                </Bubble>
                            </>
                        )}
                        <div ref={end} />
                    </div>

                    <div className="border-t px-6 py-4">
                        <div className="mb-3 flex flex-wrap gap-2">
                            {suggestions.map((suggestion) => (
                                <button
                                    key={suggestion}
                                    type="button"
                                    disabled={pending !== null}
                                    onClick={() => send(suggestion)}
                                    className="rounded-full border px-3 py-1.5 text-sm font-semibold hover:bg-muted disabled:opacity-50"
                                >
                                    {suggestion}
                                </button>
                            ))}
                            <Link
                                href={lettersCreate().url}
                                className="rounded-full border border-primary px-3 py-1.5 text-sm font-semibold text-primary hover:bg-selected"
                            >
                                Upload a BIR letter
                            </Link>
                        </div>
                        <form
                            className="flex gap-2.5"
                            onSubmit={(event) => {
                                event.preventDefault();
                                send(question);
                            }}
                        >
                            <Input
                                aria-label="Your question"
                                value={question}
                                maxLength={2000}
                                placeholder="Ask anything about your taxes"
                                onChange={(event) =>
                                    setQuestion(event.target.value)
                                }
                                className="h-12 rounded-xl"
                            />
                            <Button
                                type="submit"
                                disabled={
                                    pending !== null || question.trim() === ''
                                }
                                className="h-12 rounded-xl px-6 font-bold"
                            >
                                Send
                            </Button>
                        </form>
                        <FieldError message={error} />
                        <p className="mt-2 text-xs text-muted-foreground">
                            Using {provider} with your key.{' '}
                            <Link
                                href={data().url}
                                className="font-bold text-primary hover:underline"
                            >
                                Change
                            </Link>
                        </p>
                    </div>
                </section>

                <div className="flex flex-col gap-4">
                    <SectionCard title="Numbers it's using">
                        {last && last.figures.length > 0 ? (
                            <dl className="divide-y divide-divider text-[15px]">
                                {last.figures.map((figure) => (
                                    <div
                                        key={figure.label}
                                        className="flex justify-between gap-3 py-2"
                                    >
                                        <dt>{figure.label}</dt>
                                        <dd className="text-right font-bold tabular-nums">
                                            {figure.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                The figures the last answer looked up show here.
                            </p>
                        )}
                        <p className="mt-3 text-[13px] text-muted-foreground">
                            From the tax engine on this computer. The assistant
                            reads them but never changes or computes them.
                        </p>
                    </SectionCard>

                    <Note title="It reads, you decide">
                        It can't change anything in the app. When something
                        needs doing, it tells you where.
                    </Note>

                    {last && !last.error && (
                        <SectionCard>
                            {last.feedback === null ? (
                                <div className="flex flex-wrap gap-2.5">
                                    {(['helpful', 'wrong'] as const).map(
                                        (value) => (
                                            <Button
                                                key={value}
                                                variant="outline"
                                                className="rounded-xl font-bold"
                                                onClick={() =>
                                                    router.post(
                                                        feedback().url,
                                                        {
                                                            turn: lastIndex,
                                                            feedback: value,
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {value === 'helpful'
                                                    ? 'Helpful'
                                                    : 'This was wrong'}
                                            </Button>
                                        ),
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {last.feedback === 'helpful'
                                        ? 'Glad it helped.'
                                        : 'Thanks for saying. Check your figures on My tax, and with a CPA before you rely on this answer.'}
                                </p>
                            )}
                        </SectionCard>
                    )}
                </div>
            </div>
        </>
    );
}
