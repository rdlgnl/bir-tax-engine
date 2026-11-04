import { peso, plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Summary } from './types';

/** What the statement holds, in four tiles. */
export function SummaryTiles({ summary }: { summary: Summary }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Tile
                tone="success"
                label={`Client payments · ${plural(summary.clients, 'client')}`}
                value={`${summary.payments} · ${peso(summary.total)}`}
            />
            <Tile
                label="Not income (transfers, bills)"
                value={`${summary.ignored} ignored`}
            />
            <Tile
                label="Already recorded"
                value={`${summary.duplicates} skipped`}
                detail={
                    [
                        summary.settles > 0 &&
                            `${plural(summary.settles, 'invoice')} to mark paid`,
                        summary.filed > 0 &&
                            `${summary.filed} in filed quarters left out`,
                    ]
                        .filter(Boolean)
                        .join(' · ') || undefined
                }
            />
            <Tile
                tone={summary.answers > 0 ? 'warning' : undefined}
                label="Needs your answer"
                value={String(summary.answers)}
            />
        </div>
    );
}

function Tile({
    label,
    value,
    detail,
    tone,
}: {
    label: string;
    value: string;
    detail?: string;
    tone?: 'success' | 'warning';
}) {
    return (
        <div
            className={cn(
                'rounded-card border bg-card p-4',
                tone === 'success' && 'border-green-200 bg-success-soft',
                tone === 'warning' && 'border-warning-border bg-warning-soft',
            )}
        >
            <p
                className={cn(
                    'text-[13px] font-bold text-muted-foreground',
                    tone === 'success' && 'text-success',
                    tone === 'warning' && 'text-warning',
                )}
            >
                {label}
            </p>
            <p className="text-2xl font-extrabold">{value}</p>
            {detail && (
                <p className="text-[13px] text-muted-foreground">{detail}</p>
            )}
        </div>
    );
}
