import { Note } from '@/components/note';

/** The pill beside a corporation heading: the feature is in beta. */
export function BetaBadge() {
    // The space keeps "Beta" a word of its own in the heading's name.
    return (
        <>
            {' '}
            <span className="ml-1 inline-block rounded-full bg-warning-soft px-2.5 py-1 align-middle text-xs font-bold tracking-normal text-warning">
                Beta
            </span>
        </>
    );
}

/**
 * Why corporations are in beta: their rules aren't reviewed yet, and the
 * quarterly 20% test has no clear BIR guidance.
 */
export function BetaNote() {
    return (
        <Note tone="warning" title="Corporations are in beta">
            The corporate rules haven't been reviewed by a CPA yet, and some,
            like the 20% rate on a quarterly return, have no clear BIR guidance.
            Check each 1702Q and 1702-RT with your accountant before filing.
        </Note>
    );
}
