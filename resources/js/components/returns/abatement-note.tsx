import { Note } from '@/components/note';
import { formatDate, peso } from '@/lib/format';
import type { AbatementOffer } from '@/types';

/** Points a micro taxpayer with back taxes to RR 4-2026's abatement. */
export function AbatementNote({ offer }: { offer: AbatementOffer }) {
    return (
        <div className="mt-3">
            <Note tone="info" title="You may be able to settle this for less">
                As a micro taxpayer, you can apply under {offer.source} to clear
                tax and penalties due by the end of 2025, up to{' '}
                {peso(offer.ceiling)} a year, for a one-time {peso(offer.fee)}{' '}
                fee on Form 0605. Apply at your RDO by{' '}
                {formatDate(offer.until, 'long')}.
            </Note>
        </div>
    );
}
