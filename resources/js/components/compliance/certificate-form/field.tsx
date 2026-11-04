import { AlertTriangle } from 'lucide-react';
import { FieldError } from '@/components/field-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** A field of the 2307, flagged when it was read but not for sure. */
export function Field({
    label,
    htmlFor,
    error,
    optional,
    check = false,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    optional?: boolean;
    /** Read from the file, but not for sure. */
    check?: boolean;
    children: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                'flex flex-col gap-1.5',
                check &&
                    '[&_button]:border-warning-border [&_input]:border-warning-border [&_input]:ring-2 [&_input]:ring-warning-soft',
            )}
        >
            <Label htmlFor={htmlFor} className="text-sm font-bold">
                {label}{' '}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                )}
            </Label>
            {check && (
                <span className="flex items-center gap-1.5 text-[13px] font-bold text-warning">
                    <AlertTriangle className="size-3.5" aria-hidden />
                    Couldn't confirm this one. Please check it against the form.
                </span>
            )}
            {children}
            <FieldError message={error} />
        </div>
    );
}
