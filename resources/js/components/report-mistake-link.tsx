import { usePage } from '@inertiajs/react';

/**
 * Opens a new issue on the project's tracker for a rule that looks wrong,
 * filled in with the rule and the pack: never the taxpayer's figures.
 */
export function ReportMistakeLink({
    packVersion,
    rule,
    label = 'Report a mistake',
}: {
    packVersion: string;
    rule?: { id: string; version: number };
    label?: string;
}) {
    const { issuesUrl } = usePage().props;
    const what = rule ? `Rule ${rule.id} v${rule.version}` : 'A rule';
    const body = [
        `**Rule:** ${rule ? `${rule.id} v${rule.version}` : '(which one?)'}, rule pack ${packVersion}`,
        '',
        '**What the app does:**',
        '',
        '**What it should do, and the BIR issuance that says so** (RR, RMC or NIRC section, with a link if you have one):',
        '',
        "Please don't include your name, TIN or your own figures.",
    ].join('\n');
    const href = `${issuesUrl}/new?title=${encodeURIComponent(`${what} is wrong`)}&body=${encodeURIComponent(body)}`;

    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className="font-bold text-primary hover:underline"
        >
            {label}
        </a>
    );
}
