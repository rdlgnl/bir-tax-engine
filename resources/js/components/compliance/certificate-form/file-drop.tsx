import { FileText } from 'lucide-react';
import { useEffect, useState } from 'react';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Reading } from './types';

/**
 * Where the 2307 is dropped or chosen, shown without sending it anywhere:
 * it's uploaded only on save.
 */
export function FileDrop({
    file,
    error,
    processing,
    reading,
    found,
    toCheck,
    onChoose,
}: {
    file: File | null;
    error?: string;
    processing: boolean;
    reading: Reading | null;
    found: number;
    toCheck: number;
    onChoose: (file: File | null) => void;
}) {
    const [preview, setPreview] = useState<string | null>(null);
    const [dragging, setDragging] = useState(false);
    const isImage = file?.type.startsWith('image/');

    useEffect(() => {
        if (!file) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(file);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [file]);

    return (
        <SectionCard className="flex flex-col gap-3 p-5">
            <label
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(event) => {
                    event.preventDefault();
                    setDragging(false);
                    onChoose(event.dataTransfer.files[0] ?? null);
                }}
                className={cn(
                    'flex min-h-[420px] cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[#E3E7EC] text-sm text-slate-600 lg:min-h-[560px]',
                    dragging && 'ring-2 ring-primary',
                )}
            >
                <input
                    type="file"
                    accept="application/pdf,image/jpeg,image/png,image/webp"
                    className="sr-only"
                    onChange={(event) =>
                        onChoose(event.target.files?.[0] ?? null)
                    }
                />
                {preview && isImage ? (
                    <img
                        src={preview}
                        alt={`Preview of ${file?.name}`}
                        className="max-h-[560px] w-full object-contain"
                    />
                ) : preview ? (
                    <object
                        data={preview}
                        type="application/pdf"
                        className="h-[560px] w-full"
                        aria-label={`Preview of ${file?.name}`}
                    >
                        <FileText className="size-8" aria-hidden />
                        {file?.name}
                    </object>
                ) : (
                    <>
                        <FileText className="size-8" aria-hidden />
                        <span className="font-bold">
                            Drop the 2307 here, or click to choose
                        </span>
                        <span>PDF or photo, up to 10 MB (optional)</span>
                    </>
                )}
            </label>
            <FieldError message={error} />
            <div className="flex items-center justify-between gap-3">
                <span
                    role="status"
                    className="truncate text-sm text-muted-foreground"
                >
                    {!file
                        ? 'No file attached'
                        : processing
                          ? `Reading ${file.name}…`
                          : reading?.readable
                            ? `${plural(found, 'field')} found · ${toCheck} to check`
                            : reading
                              ? 'No text to read: type the details'
                              : file.name}
                </span>
                {file && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onChoose(null)}
                    >
                        Remove file
                    </Button>
                )}
            </div>
            <p className="text-[13px] text-muted-foreground">
                {reading && !reading.readable
                    ? "This looks like a scan or a photo, which can't be read yet. Type the details from it on the right."
                    : 'Drag a PDF or photo here. Files are read on this computer and never uploaded.'}
            </p>
        </SectionCard>
    );
}
