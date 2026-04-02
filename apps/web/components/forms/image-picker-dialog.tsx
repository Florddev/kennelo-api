"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { ImageIcon, Upload } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@workspace/ui/components/dialog";
import { cn } from "@workspace/ui/lib/utils";

function StagedFilePreview({ file }: { file: File }) {
    const url = useMemo(() => URL.createObjectURL(file), [file]);
    useEffect(() => () => URL.revokeObjectURL(url), [url]);
    // eslint-disable-next-line @next/next/no-img-element
    return <img src={url} alt={file.name} className="size-full object-cover" />;
}

type ImagePickerDialogProps = {
    value: File[];
    onChange: (files: File[]) => void;
    children: React.ReactNode;
    maxFiles?: number;
};

export function ImagePickerDialog({ value, onChange, children, maxFiles }: ImagePickerDialogProps) {
    const t = useTranslations();
    const [open, setOpen] = useState(false);
    const [staged, setStaged] = useState<File[]>([]);
    const [isDragging, setIsDragging] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    function handleOpen(isOpen: boolean) {
        setOpen(isOpen);
        if (isOpen) {
            setStaged([]);
        }
    }

    function addFiles(incoming: FileList | null) {
        if (!incoming) return;
        const newFiles = Array.from(incoming).filter(
            (file) => !staged.some((s) => s.name === file.name && s.size === file.size),
        );
        setStaged((prev) => {
            const nextFiles = [...prev, ...newFiles];

            if (!maxFiles) {
                return nextFiles;
            }

            return nextFiles.slice(-maxFiles);
        });
    }

    function handleDragOver(e: React.DragEvent) {
        e.preventDefault();
        setIsDragging(true);
    }

    function handleDragLeave(e: React.DragEvent) {
        if (!e.currentTarget.contains(e.relatedTarget as Node)) {
            setIsDragging(false);
        }
    }

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setIsDragging(false);
        addFiles(e.dataTransfer.files);
    }

    function handleImport() {
        if (maxFiles === 1) {
            onChange(staged.slice(-1));
            setOpen(false);
            return;
        }

        const merged = [...value, ...staged].filter(
            (file, index, all) =>
                all.findIndex(
                    (candidate) => candidate.name === file.name && candidate.size === file.size,
                ) === index,
        );

        onChange(maxFiles ? merged.slice(-maxFiles) : merged);
        setOpen(false);
    }

    const subtitle =
        staged.length === 0
            ? t("ui.forms.imagePicker.noFiles")
            : t("ui.forms.imagePicker.filesCount", { count: staged.length });

    return (
        <Dialog open={open} onOpenChange={handleOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t("ui.forms.imagePicker.title")}</DialogTitle>
                    <DialogDescription>{subtitle}</DialogDescription>
                </DialogHeader>

                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    onClick={() => inputRef.current?.click()}
                    className={cn(
                        "relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-10 transition-colors",
                        isDragging
                            ? "border-primary bg-primary/5"
                            : "border-muted-foreground/25 hover:border-muted-foreground/50",
                    )}
                >
                    <div
                        className={cn(
                            "flex size-12 items-center justify-center rounded-full transition-colors",
                            isDragging ? "bg-primary/10" : "bg-muted",
                        )}
                    >
                        {isDragging ? (
                            <ImageIcon className="size-5 text-primary" />
                        ) : (
                            <Upload className="size-5 text-muted-foreground" />
                        )}
                    </div>
                    <div className="flex flex-col items-center gap-1 text-center">
                        <p className="text-sm font-medium">
                            {isDragging
                                ? t("ui.forms.imagePicker.dropZoneActive")
                                : t("ui.forms.imagePicker.dropZone")}
                        </p>
                        {!isDragging && (
                            <Button
                                type="button"
                                variant="link"
                                size="sm"
                                className="h-auto p-0 text-sm"
                            >
                                {t("ui.forms.imagePicker.browse")}
                            </Button>
                        )}
                    </div>
                    <input
                        ref={inputRef}
                        type="file"
                        accept="image/*"
                        multiple={maxFiles !== 1}
                        className="hidden"
                        onChange={(e) => addFiles(e.target.files)}
                    />
                </div>

                {staged.length > 0 && (
                    <div className="grid grid-cols-4 gap-2 max-h-40 overflow-y-auto w-fit">
                        {staged.map((file) => (
                            <div
                                key={`${file.name}-${file.size}`}
                                className="aspect-square rounded-xl bg-muted overflow-hidden"
                            >
                                <StagedFilePreview file={file} />
                            </div>
                        ))}
                    </div>
                )}

                <DialogFooter>
                    <Button type="button" disabled={staged.length === 0} onClick={handleImport}>
                        {t("ui.forms.imagePicker.import")}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
