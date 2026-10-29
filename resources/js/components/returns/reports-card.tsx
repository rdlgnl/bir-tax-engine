import { usePage } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { income, packageMethod, sawt, summary } from '@/routes/reports';
import type { ReportYear } from '@/types';

/** "Reports and exports": files made from the records, downloaded straight to this computer. */
export function ReportsCard({ reports }: { reports: ReportYear[] }) {
    const errors = usePage().props.errors as Record<string, string | undefined>;
    const [year, setYear] = useState(
        reports[0]?.year ?? new Date().getFullYear(),
    );
    const [packaging, setPackaging] = useState(Boolean(errors.password));
    const [password, setPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const current = reports.find((r) => r.year === year) ?? reports[0];
    const mismatch = confirmation.length > 0 && password !== confirmation;

    return (
        <SectionCard as="section">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                    Reports and exports · {year}
                </h2>
                <div className="flex flex-wrap items-center gap-2.5">
                    {reports.length > 1 && (
                        <ChoiceChips<string>
                            label="Year"
                            value={String(year)}
                            onChange={(value) => setYear(Number(value))}
                            options={reports.map((r) => ({
                                value: String(r.year),
                                label: String(r.year),
                            }))}
                        />
                    )}
                    <Button
                        className="rounded-xl font-bold"
                        onClick={() => setPackaging(true)}
                    >
                        Export for my accountant
                    </Button>
                </div>
            </div>
            <FieldError message={errors.password} />

            <ul className="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-5">
                <Tile
                    href={summary(year).url}
                    title="Tax summary"
                    detail="For loans, visas, clients · PDF"
                />
                <Tile
                    href={income(year).url}
                    title="Income and withholding"
                    detail="Every entry · CSV, opens in Excel"
                />
                <li className="rounded-2xl bg-muted px-3.5 py-3">
                    <p className="text-[15px] font-bold">SAWT per quarter</p>
                    {current && current.sawt_quarters.length > 0 ? (
                        <p className="mt-1 flex flex-wrap gap-x-3 text-sm">
                            {current.sawt_quarters.map((q) => (
                                <a
                                    key={q}
                                    href={sawt([year, q]).url}
                                    download
                                    className="font-bold text-primary hover:underline"
                                >
                                    Q{q}
                                </a>
                            ))}
                        </p>
                    ) : (
                        <p className="text-[13px] text-muted-foreground">
                            No tax withheld this year
                        </p>
                    )}
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        Data for the BIR alphalist tool
                    </p>
                </li>
                <li
                    aria-disabled="true"
                    className="rounded-2xl bg-muted/60 px-3.5 py-3 text-slate-400"
                >
                    <p className="text-[15px] font-bold">Books of accounts</p>
                    <p className="text-[13px]">
                        Journals · PDF · coming with books
                    </p>
                </li>
                <li>
                    <button
                        type="button"
                        onClick={() => setPackaging(true)}
                        className="h-full w-full rounded-2xl bg-muted px-3.5 py-3 text-left hover:bg-divider"
                    >
                        <span className="block text-[15px] font-bold">
                            All documents
                        </span>
                        <span className="text-[13px] text-muted-foreground">
                            Returns, 2307s, reports · ZIP
                        </span>
                    </button>
                </li>
            </ul>

            <p className="mt-4 text-[13px] text-muted-foreground">
                Your records live only on this computer. Keep backups: the BIR
                can ask for records for up to 5 years after filing.
            </p>

            <Dialog open={packaging} onOpenChange={setPackaging}>
                <DialogContent className="max-w-[480px] rounded-[22px]">
                    <DialogTitle className="text-xl font-extrabold">
                        Give your accountant a copy
                    </DialogTitle>
                    <DialogDescription>
                        One password-protected ZIP with your {year} numbers,
                        returns and 2307s. You send it yourself; nothing is
                        uploaded anywhere.
                    </DialogDescription>
                    {/* A plain form, so the browser downloads the ZIP it sends back. */}
                    <form
                        method="post"
                        action={packageMethod(year).url}
                        onSubmit={(event) => {
                            if (mismatch || password.length < 8) {
                                event.preventDefault();

                                return;
                            }

                            setTimeout(() => {
                                setPackaging(false);
                                setPassword('');
                                setConfirmation('');
                            }, 300);
                        }}
                        className="flex flex-col gap-3"
                    >
                        <input
                            type="hidden"
                            name="_token"
                            value={
                                document.querySelector<HTMLMetaElement>(
                                    'meta[name="csrf-token"]',
                                )?.content ?? ''
                            }
                        />
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="package-password">
                                Password for the ZIP
                            </Label>
                            <Input
                                id="package-password"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                minLength={8}
                                required
                                value={password}
                                onChange={(event) =>
                                    setPassword(event.target.value)
                                }
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="package-password-confirmation">
                                Type it again
                            </Label>
                            <Input
                                id="package-password-confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                required
                                value={confirmation}
                                onChange={(event) =>
                                    setConfirmation(event.target.value)
                                }
                                aria-invalid={mismatch || undefined}
                            />
                        </div>
                        <FieldError
                            message={
                                mismatch
                                    ? 'The two passwords don’t match.'
                                    : undefined
                            }
                        />
                        <p className="flex items-start gap-2 rounded-xl bg-muted p-3 text-[13px] text-slate-700">
                            <Lock
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden
                            />
                            Tell your accountant the password another way (a
                            call or text), not in the same email as the file. It
                            isn't stored anywhere, so keep a note of it.
                        </p>
                        <Button
                            type="submit"
                            disabled={mismatch || password.length < 8}
                            className={cn(
                                'h-12 rounded-xl text-base font-bold',
                            )}
                        >
                            Download the ZIP
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </SectionCard>
    );
}

function Tile({
    href,
    title,
    detail,
}: {
    href: string;
    title: string;
    detail: string;
}) {
    return (
        <li>
            <a
                href={href}
                download
                className="block h-full rounded-2xl bg-muted px-3.5 py-3 hover:bg-divider"
            >
                <span className="block text-[15px] font-bold">{title}</span>
                <span className="text-[13px] text-muted-foreground">
                    {detail}
                </span>
            </a>
        </li>
    );
}
