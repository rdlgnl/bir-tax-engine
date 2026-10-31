import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { TaxProfileSummary } from '@/types';

/** "You're classified as", "Taxes we'll handle" and "To do next". */
export function ClassificationList({
    profile,
    showTodos = true,
}: {
    profile: TaxProfileSummary;
    showTodos?: boolean;
}) {
    return (
        <div className="flex flex-col gap-6">
            <Block title="You're classified as">
                {profile.classification.map((item) => (
                    <li
                        key={item.label}
                        className="flex items-center gap-2.5 py-2.5"
                    >
                        <Check
                            className="size-4 shrink-0 text-success"
                            aria-hidden
                        />
                        <span className="flex-1 text-[15px] font-semibold">
                            {item.label}
                        </span>
                        <span className="text-[13px] text-muted-foreground">
                            {item.source}
                        </span>
                    </li>
                ))}
            </Block>
            <Block title="Taxes we'll handle">
                {profile.taxes.map((tax) => (
                    <li
                        key={tax.label}
                        className="flex items-center justify-between gap-3 py-2.5 text-[15px]"
                    >
                        <span className="font-semibold">{tax.label}</span>
                        <span className="text-right text-muted-foreground">
                            {tax.detail}
                        </span>
                    </li>
                ))}
            </Block>
            {showTodos && profile.todos.length > 0 && (
                <Block title="To do next">
                    {profile.todos.map((todo, index) => (
                        <li
                            key={todo.label}
                            className="flex items-start gap-3 py-2.5 text-[15px]"
                        >
                            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-extrabold text-accent-foreground">
                                {index + 1}
                            </span>
                            <span>
                                {todo.href ? (
                                    <Link
                                        href={todo.href}
                                        className="font-semibold text-primary hover:underline"
                                    >
                                        {todo.label}
                                    </Link>
                                ) : (
                                    <span className="font-semibold">
                                        {todo.label}
                                    </span>
                                )}
                                {todo.detail && (
                                    <span className="block text-[13px] text-muted-foreground">
                                        {todo.detail}
                                    </span>
                                )}
                            </span>
                        </li>
                    ))}
                </Block>
            )}
        </div>
    );
}

function Block({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section>
            <h2 className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                {title}
            </h2>
            <ul className="mt-1 divide-y divide-divider">{children}</ul>
        </section>
    );
}
