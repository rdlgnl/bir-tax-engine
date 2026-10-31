const pesoFormat = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

/** ₱14,000 or ₱1,234.50 */
export function peso(amount: number): string {
    return pesoFormat.format(Math.abs(amount)).replace('PHP', '₱');
}

const formFormat = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/** 200,000.00, the way BIR forms print amounts. */
export function formAmount(amount: number): string {
    return formFormat.format(amount);
}

/** −₱2,000 for credits, +₱2,400 for additions. Uses a true minus sign, as the design does. */
export function signedPeso(amount: number): string {
    if (amount === 0) {
        return peso(0);
    }

    return `${amount < 0 ? '−' : '+'}${peso(amount)}`;
}

/** −₱2,000, for amounts that are always shown as deductions. */
export function minusPeso(amount: number): string {
    return `−${peso(amount)}`;
}

/** ₱3M, ₱3.2M, ₱250k */
export function shortPeso(amount: number): string {
    if (amount >= 1_000_000) {
        return `₱${Number((amount / 1_000_000).toFixed(1))}M`;
    }

    if (amount >= 1_000) {
        return `₱${Math.round(amount / 1_000)}k`;
    }

    return peso(amount);
}

/**
 * Dates arrive as YYYY-MM-DD in Philippine time. Build them from their parts
 * so the browser's timezone can never move a deadline to another day.
 */
export function parseDate(date: string): Date {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day);
}

type DateStyle = 'short' | 'weekday' | 'long' | 'full' | 'day';

const dateFormats: Record<DateStyle, Intl.DateTimeFormatOptions> = {
    day: { month: 'short', day: 'numeric' }, // Nov 16
    short: { weekday: 'short', month: 'short', day: 'numeric' }, // Mon, Nov 16
    weekday: { weekday: 'long', month: 'long', day: 'numeric' }, // Monday, November 16
    long: { month: 'long', day: 'numeric', year: 'numeric' }, // November 16, 2026
    full: { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' },
};

export function formatDate(date: string, style: DateStyle = 'day'): string {
    return new Intl.DateTimeFormat('en-US', dateFormats[style]).format(
        parseDate(date),
    );
}

/** Jan–Sep, for a cumulative quarter. */
export function periodLabel(quarter: number): string {
    return quarter === 4 ? 'Jan–Dec' : `Jan–${monthName(quarter * 3)}`;
}

/** Jul–Sep */
export function quarterMonths(quarter: number): string {
    return `${monthName(quarter * 3 - 2)}–${monthName(quarter * 3)}`;
}

function monthName(month: number): string {
    return new Intl.DateTimeFormat('en-US', { month: 'short' }).format(
        new Date(2000, month - 1, 1),
    );
}

export function plural(
    count: number,
    word: string,
    plural = `${word}s`,
): string {
    return `${count} ${count === 1 ? word : plural}`;
}

/** For number inputs: keep digits and one decimal point. */
export function cleanAmount(value: string): string {
    const cleaned = value.replace(/[^\d.]/g, '');
    const [whole, ...decimals] = cleaned.split('.');

    return decimals.length
        ? `${whole}.${decimals.join('').slice(0, 2)}`
        : whole;
}

export function todayIso(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/** 52 MB, 840 KB */
export function fileSize(bytes: number): string {
    if (bytes >= 1_000_000) {
        return `${Number((bytes / 1_000_000).toFixed(1))} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1_000))} KB`;
}

/** "Today, 1:12 PM" or "Oct 28, 2026, 1:12 PM", for a moment like a backup. */
export function formatMoment(iso: string): string {
    const date = new Date(iso);
    const time = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);

    if (date.toDateString() === new Date().toDateString()) {
        return `Today, ${time}`;
    }

    return `${new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date)}, ${time}`;
}
