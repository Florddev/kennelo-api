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
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";

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
    mode?: "dialog" | "direct";
};

export function ImagePickerDialog({
    value,
    onChange,
    children,
    maxFiles,
    mode = "dialog",
}: ImagePickerDialogProps) {
    const t = useTranslations();
    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);
    const [staged, setStaged] = useState<File[]>([]);
    const [isDragging, setIsDragging] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const directInputRef = useRef<HTMLInputElement>(null);

    function handleOpen(isOpen: boolean) {
        setOpen(isOpen);
        if (isOpen) setStaged([]);
    }

    function addFiles(incoming: FileList | null) {
        if (!incoming) return;
        const newFiles = Array.from(incoming).filter(
            (file) => !staged.some((s) => s.name === file.name && s.size === file.size),
        );
        setStaged((prev) => {
            const nextFiles = [...prev, ...newFiles];
            if (!maxFiles) return nextFiles;
            return nextFiles.slice(-maxFiles);
        });
    }

    function commitFiles(incoming: FileList | null) {
        if (!incoming) return;
        const newFiles = Array.from(incoming);
        if (maxFiles === 1) {
            onChange(newFiles.slice(-1));
            return;
        }
        const merged = [...value, ...newFiles].filter(
            (file, index, all) =>
                all.findIndex((c) => c.name === file.name && c.size === file.size) === index,
        );
        onChange(maxFiles ? merged.slice(-maxFiles) : merged);
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

    if (mode === "direct") {
        return (
            <div
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={(e) => {
                    e.preventDefault();
                    setIsDragging(false);
                    commitFiles(e.dataTransfer.files);
                }}
                onClick={() => directInputRef.current?.click()}
                data-dragging={isDragging || undefined}
                className="cursor-pointer"
            >
                {children}
                <input
                    ref={directInputRef}
                    type="file"
                    accept="image/*"
                    multiple={maxFiles !== 1}
                    className="hidden"
                    onChange={(e) => {
                        commitFiles(e.target.files);
                        e.target.value = "";
                    }}
                />
            </div>
        );
    }

    const subtitle =
        staged.length === 0
            ? t("ui.forms.imagePicker.noFiles")
            : t("ui.forms.imagePicker.filesCount", { count: staged.length });

    const dropZone = (
        <>
            <div
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={(e) => {
                    e.preventDefault();
                    setIsDragging(false);
                    addFiles(e.dataTransfer.files);
                }}
                onClick={() => inputRef.current?.click()}
                className={cn(
                    "relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-10 transition-colors cursor-pointer",
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
        </>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={handleOpen}>
                <DrawerTrigger asChild>{children}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader className="pb-0">
                        <DrawerTitle>{t("ui.forms.imagePicker.title")}</DrawerTitle>
                        <DrawerDescription>{subtitle}</DrawerDescription>
                    </DrawerHeader>
                    <div className="py-4 flex flex-col gap-4 overflow-y-auto">{dropZone}</div>
                    <DrawerFooter className="p-0 flex-row gap-2">
                        {/* <DrawerClose asChild>
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                className="w-1/2">
                                {t("common.actions.cancel")}
                            </Button>
                        </DrawerClose> */}
                        <Button
                            type="button"
                            size="xl"
                            disabled={staged.length === 0}
                            onClick={handleImport}
                            className="w-full"
                        >
                            {t("ui.forms.imagePicker.import")}
                        </Button>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Dialog open={open} onOpenChange={handleOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t("ui.forms.imagePicker.title")}</DialogTitle>
                    <DialogDescription>{subtitle}</DialogDescription>
                </DialogHeader>
                <div className="flex flex-col gap-4">{dropZone}</div>
                <DialogFooter>
                    <Button type="button" disabled={staged.length === 0} onClick={handleImport}>
                        {t("ui.forms.imagePicker.import")}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
