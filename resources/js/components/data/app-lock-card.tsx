import { router, useForm } from '@inertiajs/react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update as updateData } from '@/routes/data';
import { disable, now, settings } from '@/routes/lock';

type Lock = { enabled: boolean; lock_after: number; options: number[] };

const digits = (value: string) => value.replace(/\D/g, '').slice(0, 6);

/**
 * The app lock under Your data: turn it on, change the PIN or the time
 * away, lock now, or turn it off. Changes to an existing lock ask for the
 * current PIN. Also the deadline reminders switch.
 */
export function AppLockCard({
    lock,
    reminders,
}: {
    lock: Lock;
    reminders: boolean;
}) {
    const form = useForm({
        current_pin: '',
        pin: '',
        pin_confirmation: '',
        lock_after: lock.lock_after,
    });
    const errors = form.errors as Record<string, string | undefined>;
    const pinInput = (name: 'current_pin' | 'pin' | 'pin_confirmation') => ({
        id: `lock-${name}`,
        type: 'password',
        inputMode: 'numeric' as const,
        autoComplete: 'off',
        value: form.data[name],
        onChange: (event: React.ChangeEvent<HTMLInputElement>) =>
            form.setData(name, digits(event.target.value)),
        'aria-invalid': errors[name] ? true : undefined,
        className: 'h-11 rounded-xl bg-card tracking-[0.3em]',
    });

    return (
        <SectionCard title="App lock">
            <p className="text-[15px] font-bold">
                {lock.enabled
                    ? `On · locks after ${lock.lock_after} min away`
                    : 'Off'}
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
                A PIN between your records and anyone else at this computer. If
                you forget it, restore a backup on a fresh install.
            </p>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(settings(), {
                        preserveScroll: true,
                        onSuccess: () =>
                            form.reset(
                                'current_pin',
                                'pin',
                                'pin_confirmation',
                            ),
                    });
                }}
                className="mt-4 flex flex-col gap-3 border-t border-divider pt-4"
            >
                {lock.enabled && (
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="lock-current_pin">Current PIN</Label>
                        <Input {...pinInput('current_pin')} />
                        <FieldError message={errors.current_pin} />
                    </div>
                )}
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="lock-pin">
                            {lock.enabled
                                ? 'New PIN (optional)'
                                : 'PIN, 4–6 digits'}
                        </Label>
                        <Input {...pinInput('pin')} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="lock-pin_confirmation">
                            Type it again
                        </Label>
                        <Input {...pinInput('pin_confirmation')} />
                    </div>
                </div>
                <FieldError message={errors.pin} />
                <div className="flex flex-col gap-1.5">
                    <span className="text-sm font-bold">Lock after</span>
                    <ChoiceChips<string>
                        label="Lock after"
                        variant="segmented"
                        value={String(form.data.lock_after)}
                        onChange={(value) =>
                            form.setData('lock_after', Number(value))
                        }
                        options={lock.options.map((minutes) => ({
                            value: String(minutes),
                            label: `${minutes} min`,
                        }))}
                    />
                    <FieldError message={errors.lock_after} />
                </div>
                <div className="flex flex-wrap gap-2.5">
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-xl font-bold"
                    >
                        {lock.enabled ? 'Save' : 'Turn on'}
                    </Button>
                    {lock.enabled && (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-xl font-bold"
                                onClick={() => router.post(now().url)}
                            >
                                Lock now
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.data.current_pin.length < 4}
                                className="rounded-xl font-bold text-destructive"
                                onClick={() =>
                                    router.delete(disable().url, {
                                        data: {
                                            current_pin: form.data.current_pin,
                                        },
                                        preserveScroll: true,
                                        onSuccess: () => form.reset(),
                                        onError: (e) =>
                                            form.setError(
                                                'current_pin',
                                                e.current_pin ?? '',
                                            ),
                                    })
                                }
                            >
                                Turn off
                            </Button>
                        </>
                    )}
                </div>
            </form>

            <label className="mt-4 flex items-start gap-3 border-t border-divider pt-4 text-[15px]">
                <Checkbox
                    checked={reminders}
                    onCheckedChange={(checked) => {
                        if (
                            checked === true &&
                            'Notification' in window &&
                            Notification.permission === 'default'
                        ) {
                            void Notification.requestPermission();
                        }

                        router.put(
                            updateData().url,
                            { reminders: checked === true },
                            { preserveScroll: true },
                        );
                    }}
                    className="mt-0.5"
                />
                <span>
                    <span className="block font-semibold">
                        Deadline reminders
                    </span>
                    <span className="text-[13px] text-muted-foreground">
                        7 days and 1 day before each due date, while BuwisBuddy
                        is open in your browser.
                    </span>
                </span>
            </label>
        </SectionCard>
    );
}
