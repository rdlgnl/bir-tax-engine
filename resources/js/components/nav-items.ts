import type { LucideIcon } from 'lucide-react';
import {
    BarChart3,
    CalendarDays,
    FileCheck2,
    FileText,
    History,
    Home,
    ListChecks,
    MessageCircleQuestion,
    UserRound,
} from 'lucide-react';
import { calendar, history, home, profile } from '@/routes';
import { show as complianceShow } from '@/routes/compliance';
import { index as returnsIndex } from '@/routes/returns';
import { show as taxShow } from '@/routes/tax';
import { index as transactionsIndex } from '@/routes/transactions';

export type NavItem = {
    label: string;
    icon: LucideIcon;
    /** Null for screens that come in a later update. */
    href: string | null;
    /** URL prefixes that mark this item active. */
    match: string[];
};

export const navItems: NavItem[] = [
    { label: 'Home', icon: Home, href: home.url(), match: ['/home'] },
    {
        label: 'Transactions',
        icon: ListChecks,
        href: transactionsIndex.url(),
        match: ['/transactions', '/loans'],
    },
    { label: 'My tax', icon: BarChart3, href: taxShow.url(), match: ['/tax'] },
    {
        label: 'Compliance & 2307s',
        icon: FileCheck2,
        href: complianceShow.url(),
        match: ['/compliance', '/certificates'],
    },
    {
        label: 'Calendar',
        icon: CalendarDays,
        href: calendar.url(),
        match: ['/calendar'],
    },
    {
        label: 'File a return',
        icon: FileText,
        href: returnsIndex.url(),
        match: ['/file', '/withholding'],
    },
    {
        label: 'History & reports',
        icon: History,
        href: history.url(),
        match: ['/history', '/books'],
    },
    { label: 'Ask', icon: MessageCircleQuestion, href: null, match: [] },
    {
        label: 'Profile & settings',
        icon: UserRound,
        href: profile.url(),
        match: ['/profile', '/data', '/2316'],
    },
];

export function isActive(item: NavItem, url: string): boolean {
    return item.match.some((prefix) => url.startsWith(prefix));
}
