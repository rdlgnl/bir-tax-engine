export type CertificateForm = {
    payor_name: string;
    payor_tin: string;
    year: number;
    quarter: number;
    amount_paid: string;
    tax_withheld: string;
    atc_code: string;
    file: File | null;
};

export type ReadField<T> = { value: T | null; sure: boolean };

/** App\Compliance\Services\CertificateReader */
export type Reading = {
    readable: boolean;
    fields: {
        payor_name: ReadField<string>;
        payor_tin: ReadField<string>;
        year: ReadField<number>;
        quarter: ReadField<number>;
        amount_paid: ReadField<number>;
        tax_withheld: ReadField<number>;
        atc_code: ReadField<string>;
    };
};

const nothing = { value: null, sure: false };

/** What a file the reader can't open reads as: typed in, like a photo. */
export const UNREADABLE: Reading = {
    readable: false,
    fields: {
        payor_name: nothing,
        payor_tin: nothing,
        year: nothing,
        quarter: nothing,
        amount_paid: nothing,
        tax_withheld: nothing,
        atc_code: nothing,
    },
};

/** The fields a reading fills, the period counting once. */
export type ReadKey =
    | 'payor_name'
    | 'payor_tin'
    | 'period'
    | 'amount_paid'
    | 'tax_withheld'
    | 'atc_code';

/** How the certificate compares with the payments recorded from that client. */
export type Match = {
    status: 'exact' | 'partial' | 'none';
    withheld: number;
    payments: {
        id: number;
        counterparty: string;
        occurred_on: string;
        amount: number;
        tax_withheld: number;
    }[];
};
