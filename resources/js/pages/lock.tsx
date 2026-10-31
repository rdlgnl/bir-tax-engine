import { Head, useForm } from '@inertiajs/react';
import { Lock as LockIcon } from 'lucide-react';
import { Brand } from '@/components/brand-mark';
import { FieldError } from '@/components/field-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { plural } from '@/lib/format';
import { unlock } from '@/routes/lock';

export default function Lock({ lockAfter }: { lockAfter: number }) {
    const form = useForm({ pin: '' });

    return (
        <>
            <Head title="Locked" />

            <div className="flex min-h-dvh flex-col items-center justify-center bg-background px-4 py-10">
                <div className="w-full max-w-[400px]">
                    <div className="mb-8 flex justify-center">
                        <Brand />
                    </div>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.submit(unlock(), {
                                onError: () => form.reset('pin'),
                            });
                        }}
                        className="flex flex-col gap-4 rounded-card border bg-card p-6"
                    >
                        <span className="mx-auto flex size-12 items-center justify-center rounded-full bg-accent text-primary">
                            <LockIcon className="size-5" aria-hidden />
                        </span>
                        <div className="text-center">
                            <h1 className="text-2xl font-extrabold">
                                BuwisBuddy is locked
                            </h1>
                            <p className="text-[15px] text-muted-foreground">
                                Locks after {plural(lockAfter, 'minute')} away
                            </p>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="pin">PIN</Label>
                            <Input
                                id="pin"
                                type="password"
                                inputMode="numeric"
                                autoComplete="off"
                                autoFocus
                                maxLength={6}
                                value={form.data.pin}
                                onChange={(event) =>
                                    form.setData(
                                        'pin',
                                        event.target.value.replace(/\D/g, ''),
                                    )
                                }
                                aria-invalid={
                                    form.errors.pin ? true : undefined
                                }
                                aria-describedby={
                                    form.errors.pin ? 'pin-error' : undefined
                                }
                                className="h-12 rounded-xl bg-card text-center text-xl tracking-[0.5em]"
                            />
                            <FieldError
                                id="pin-error"
                                message={form.errors.pin}
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={
                                form.data.pin.length < 4 || form.processing
                            }
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            Unlock
                        </Button>
                        <p className="text-center text-[13px] text-muted-foreground">
                            Forgot PIN? Restore a backup on a fresh install.
                        </p>
                    </form>
                </div>
            </div>
        </>
    );
}
