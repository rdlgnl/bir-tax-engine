export function FieldError({ id, message }: { id?: string; message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p id={id} className="text-[13px] font-semibold text-destructive">
            {message}
        </p>
    );
}
