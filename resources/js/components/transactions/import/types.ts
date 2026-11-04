/** What the app decided about one line of an imported statement. */
export type Status =
    | 'import'
    | 'settles'
    | 'ignore'
    | 'duplicate'
    | 'answer'
    | 'filed'
    | 'invalid';

export type Row = {
    index: number;
    date: string | null;
    description: string;
    received: number;
    status: Status;
    client: string | null;
    amount: number;
    withheld: number;
    reason: string;
};

/** Which column of the statement holds what; null for none. */
export type Mapping = {
    date: number | null;
    description: number | null;
    amount: number | null;
    credit: number | null;
    debit: number | null;
};

export type Statement = {
    name: string;
    columns: string[];
    mapping: Mapping;
    matched: boolean;
    lines: number;
};

export type Summary = {
    payments: number;
    total: number;
    clients: number;
    ignored: number;
    duplicates: number;
    settles: number;
    answers: number;
    filed: number;
    invalid: number;
};

/** The taxpayer's answer for a line the app couldn't place. */
export type Answer = { kind: 'payment' | 'personal'; client: string };
