import { Link } from '@inertiajs/react';
import { SectionCard } from '@/components/section-card';
import { payroll } from '@/routes';
import { show as corporationShow } from '@/routes/corporation';
import { create as lettersCreate } from '@/routes/letters';
import { create as loansCreate } from '@/routes/loans';
import { marketplace } from '@/routes/transactions';

/** Where each of the other things that happen is recorded. */
const PAGES: { href: string; title: string; detail: string }[] = [
    {
        href: marketplace().url,
        title: 'I sell on an online marketplace',
        detail: "Import the payout report: sales, fees and the platform's withholding.",
    },
    {
        href: loansCreate().url,
        title: 'I signed a loan',
        detail: 'Documentary stamp tax may be due on Form 2000.',
    },
    {
        href: payroll().url,
        title: 'I pay an employee',
        detail: 'Tax withheld on their pay, remitted monthly on the 1601-C.',
    },
    {
        href: lettersCreate().url,
        title: 'I got a letter from the BIR',
        detail: 'See what your records say, and what to send or bring.',
    },
    {
        href: corporationShow().url,
        title: 'I run a corporation',
        detail: 'Its quarterly 1702Q, from the figures in its books.',
    },
];

/** What to do about the rest, with nothing to record here. */
const NOTES: [string, string][] = [
    [
        'I refunded a client',
        'Lowers your sales for the quarter. For now, edit the original payment.',
    ],
    [
        'Other business income',
        'Selling old equipment, side commissions: record it as money received.',
    ],
    ['Bank interest', 'Already taxed 20% by the bank. Nothing to do.'],
    ['Gift or money from family', 'Not business income. Nothing to record.'],
];

export function OtherThings() {
    return (
        <SectionCard title="Other things that happened">
            <ul className="grid gap-x-6 sm:grid-cols-2 xl:grid-cols-4">
                {PAGES.map((page) => (
                    <li
                        key={page.title}
                        className="border-t border-divider py-3"
                    >
                        <Link
                            href={page.href}
                            className="block hover:underline"
                        >
                            <span className="block text-[15px] font-bold text-primary">
                                {page.title}
                            </span>
                            <span className="text-[13px] text-muted-foreground">
                                {page.detail}
                            </span>
                        </Link>
                    </li>
                ))}
                {NOTES.map(([title, detail]) => (
                    <li key={title} className="border-t border-divider py-3">
                        <span className="block text-[15px] font-bold">
                            {title}
                        </span>
                        <span className="text-[13px] text-muted-foreground">
                            {detail}
                        </span>
                    </li>
                ))}
            </ul>
        </SectionCard>
    );
}
