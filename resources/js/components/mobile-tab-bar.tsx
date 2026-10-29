import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { isActive, navItems } from '@/components/nav-items';
import { cn } from '@/lib/utils';

const tabs = ['Home', 'Transactions', 'Calendar', 'Profile & settings'];

export function MobileTabBar({ onAdd }: { onAdd: () => void }) {
    const { url } = usePage();
    const items = navItems.filter((item) => tabs.includes(item.label));
    const tab = (label: string) => {
        const item = items.find((i) => i.label === label)!;
        const Icon = item.icon;
        const active = isActive(item, url);

        return (
            <Link
                key={item.label}
                href={item.href!}
                aria-current={active ? 'page' : undefined}
                className={cn(
                    'flex flex-1 flex-col items-center gap-1 py-2 text-[11px] font-bold text-slate-600',
                    active && 'text-primary',
                )}
            >
                <Icon className="size-6" aria-hidden />
                {label === 'Profile & settings' ? 'Me' : label}
            </Link>
        );
    };

    return (
        <nav
            aria-label="Main"
            className="fixed inset-x-0 bottom-0 z-40 flex items-end border-t bg-card px-2 pb-[max(env(safe-area-inset-bottom),8px)] lg:hidden"
        >
            {tab('Home')}
            {tab('Transactions')}
            <button
                type="button"
                onClick={onAdd}
                className="flex flex-1 flex-col items-center gap-1 py-2 text-[11px] font-bold text-slate-600"
            >
                <span className="flex h-9 w-14 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                    <Plus className="size-6" aria-hidden />
                </span>
                Add
            </button>
            {tab('Calendar')}
            {tab('Profile & settings')}
        </nav>
    );
}
