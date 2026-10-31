/** App\Data\Services\LocalData::summary() */
export type DataSummary = {
    records: number;
    first_year: number | null;
    last_year: number | null;
    certificates: number;
    documents: number;
    filings: number;
};

export type LastBackup = {
    at: string;
    file: string;
    folder: string;
    size: number;
};

/** What a backup file says it holds, before its password. */
export type BackupManifest = {
    made_at: string;
    device: string;
    rule_pack: string;
    summary: DataSummary;
    file: string;
};
