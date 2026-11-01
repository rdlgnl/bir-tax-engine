/** App\Letters\Controllers\LetterController */
export type BirLetter = {
    id: number;
    kind: string;
    kind_label: string;
    needs_help: boolean;
    dated_on: string | null;
    office: string | null;
    form: string | null;
    period: string;
    reply_by: string | null;
    notes: string | null;
    has_file: boolean;
    original_filename: string | null;
    resolved_on: string | null;
};

/** App\Letters\Services\LetterCheck */
export type LetterCheck = {
    result: 'filed' | 'not_needed' | 'missing' | 'income' | 'none';
    title: string;
    detail: string;
    url: string | null;
    documents: {
        title: string;
        detail: string;
        status: string;
        url: string | null;
    }[];
};
