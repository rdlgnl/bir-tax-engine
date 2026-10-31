import { Download } from 'lucide-react';

/** The dark card that downloads a file straight to this computer. */
export function DownloadCard({
    href,
    title,
    detail,
    disabled = false,
}: {
    href: string;
    title: string;
    detail: string;
    disabled?: boolean;
}) {
    const content = (
        <>
            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary">
                <Download className="size-5" aria-hidden />
            </span>
            <span>
                <span className="block text-lg font-extrabold">{title}</span>
                <span className="text-sm text-slate-300">{detail}</span>
            </span>
        </>
    );

    if (disabled) {
        return (
            <div
                aria-disabled="true"
                className="flex items-center gap-4 rounded-card bg-ink/60 p-5 text-ink-foreground"
            >
                {content}
            </div>
        );
    }

    return (
        <a
            href={href}
            download
            className="flex items-center gap-4 rounded-card bg-ink p-5 text-ink-foreground hover:bg-slate-800"
        >
            {content}
        </a>
    );
}
