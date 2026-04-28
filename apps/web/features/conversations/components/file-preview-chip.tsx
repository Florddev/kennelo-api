import { File, X } from "lucide-react";
import Image from "next/image";
import { useEffect, useState } from "react";

export function FilePreviewChip({ file, onRemove }: { file: File; onRemove: () => void }) {
    const isImage = file.type.startsWith("image/");
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!isImage) return;
        let cancelled = false;
        const reader = new FileReader();
        reader.onload = (e) => {
            if (!cancelled) setPreview((e.target?.result as string) ?? null);
        };
        reader.readAsDataURL(file);
        return () => {
            cancelled = true;
        };
    }, [file, isImage]);

    return (
        <div data-slot="file-preview-chip" className="relative flex-shrink-0">
            {isImage && preview ? (
                <Image
                    src={preview}
                    alt={file.name}
                    width={64}
                    height={64}
                    unoptimized
                    className="h-16 w-16 rounded-sm object-cover"
                />
            ) : (
                <div className="flex h-16 w-16 flex-col items-center justify-center gap-1 rounded-sm bg-muted px-1 text-muted-foreground">
                    <File size={20} />
                    <span className="w-full truncate text-center text-[10px] leading-tight">
                        {file.name}
                    </span>
                </div>
            )}
            <button
                onClick={onRemove}
                className="absolute end-1.5 top-1.5 flex size-4 items-center justify-center rounded-full bg-foreground text-background"
            >
                <X size={10} />
            </button>
        </div>
    );
}
