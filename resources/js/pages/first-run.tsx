import { Head, router, useForm } from '@inertiajs/react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { skip, store } from '@/routes/first-run';

type Props = {
    lockOptions: number[];
    defaultLockAfter: number;
};

/** Ask the browser now, while the click counts as the person's own action. */
function askForNotifications() {
    if ('Notification' in window && Notification.permission === 'default') {
        void Notification.requestPermission();
    }
}

export default function FirstRun({ lockOptions, defaultLockAfter }: Props) {
    const form = useForm({
        pin: '',
        pin_confirmation: '',
        lock_after: defaultLockAfter,
        reminders: true,
    });
    const mismatch =
        form.data.pin_confirmation.length > 0 &&
        form.data.pin !== form.data.pin_confirmation;
    const pinField = (name: 'pin' | 'pin_confirmation') => ({
        type: 'password',
        inputMode: 'numeric' as const,
        autoComplete: 'off',
        maxLength: 6,
        value: form.data[name],
        onChange: (event: React.ChangeEvent<HTMLInputElement>) =>
            form.setData(name, event.target.value.replace(/\D/g, '')),
        className: 'h-11 rounded-xl bg-card tracking-[0.3em]',
    });

    return (
        <>
            <Head title="Two quick things" />

            <header className="mb-6">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    Two quick things
                </h1>
                <p className="text-[15px] text-muted-foreground">
                    Then you're done setting up
                </p>
            </header>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    askForNotifications();
                    form.submit(store());
                }}
                className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <div className="flex flex-col gap-4">
                    <SectionCard title="1 · Lock the app (recommended)">
                        <div className="flex justify-between gap-3 text-[15px]">
                            <span>Why</span>
                            <span className="text-right font-bold">
                                Your TIN, income and documents are on this
                                computer
                            </span>
                        </div>
                        <p className="mt-2 text-sm text-muted-foreground">
                            The PIN locks the app's screens; it doesn't encrypt
                            the files. Your computer's login and disk encryption
                            protect those.
                        </p>
                    </SectionCard>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="pin">Choose a 4–6 digit PIN</Label>
                        <Input
                            id="pin"
                            {...pinField('pin')}
                            aria-invalid={form.errors.pin ? true : undefined}
                        />
                        <FieldError message={form.errors.pin} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="pin-confirmation">Type it again</Label>
                        <Input
                            id="pin-confirmation"
                            {...pinField('pin_confirmation')}
                            aria-invalid={mismatch || undefined}
                        />
                        <FieldError
                            message={
                                mismatch
                                    ? 'The two PINs don’t match.'
                                    : undefined
                            }
                        />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">Lock after</span>
                        <ChoiceChips<string>
                            label="Lock after"
                            variant="segmented"
                            value={String(form.data.lock_after)}
                            onChange={(value) =>
                                form.setData('lock_after', Number(value))
                            }
                            options={lockOptions.map((minutes) => ({
                                value: String(minutes),
                                label: `${minutes} min`,
                            }))}
                            className="max-w-md"
                        />
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    <SectionCard title="2 · Deadline reminders">
                        <dl className="divide-y divide-divider text-[15px]">
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>When</dt>
                                <dd className="text-right font-bold">
                                    7 days and 1 day before each due date
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Also</dt>
                                <dd className="text-right font-bold">
                                    Backup reminder each month
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>
                    <Note title="Your browser will ask next">
                        Choose "Allow" so reminders can appear while BuwisBuddy
                        is open. Nothing is sent anywhere.
                    </Note>
                    <Note tone="warning" title="If you don't allow">
                        You'll only see deadlines on Home. You can turn
                        reminders on later under Your data.
                    </Note>

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            type="button"
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                            onClick={() => router.post(skip().url)}
                        >
                            Skip for now
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                mismatch ||
                                form.data.pin.length < 4
                            }
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            Set PIN and allow reminders
                        </Button>
                    </div>
                </div>
            </form>
        </>
    );
}
