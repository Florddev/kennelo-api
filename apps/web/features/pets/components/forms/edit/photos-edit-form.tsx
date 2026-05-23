"use client";

import React, { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { X } from "lucide-react";
import { GalleryAdd, TrashBinTrash } from "@solar-icons/react";
import Image from "next/image";
import { Button } from "@workspace/ui/components/button";
import { addPetImages, deletePetImage, type PetModel } from "@workspace/modules/pets";
import { ImagePickerDialog } from "@/components/forms/image-picker-dialog";
import { useAsyncState } from "@/hooks/use-async-state";
import { toast } from "sonner";
import { cn } from "@workspace/ui/lib/utils";
import { RowLabel } from "@/components/forms/inline-inputs/shared";
import { useIsMobile } from "@/hooks/use-mobile";

function FilePreview({ file, alt }: { file: File; alt: string }) {
    const url = useMemo(() => URL.createObjectURL(file), [file]);
    useEffect(() => () => URL.revokeObjectURL(url), [url]);
    return (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={url} alt={alt} className="size-full object-cover" />
    );
}

export function PhotosEditForm({ pet }: { pet: PetModel }): React.ReactElement {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();
    const [newImageFiles, setNewImageFiles] = useState<File[]>([]);
    const [deletingImageId, setDeletingImageId] = useState<string | null>(null);
    const isMobile = useIsMobile();

    const invalidate = () =>
        queryClient.invalidateQueries({ queryKey: ["pets", "detail", pet.id] });

    const galleryImages = pet.avatarUrl ? pet.images : pet.images.slice(1);

    const removeNewFile = (file: File) =>
        setNewImageFiles((prev) =>
            prev.filter((f) => f.name !== file.name || f.size !== file.size),
        );

    const handleAddImages = async () => {
        if (newImageFiles.length === 0) return;
        await execute(() => addPetImages(pet.id, newImageFiles), {
            onSuccess: () => {
                invalidate();
                setNewImageFiles([]);
                toast.success(t("features.pets.edit.saveSuccess"));
            },
        });
    };

    const handleDeleteImage = async (imageId: string) => {
        setDeletingImageId(imageId);
        await execute(() => deletePetImage(pet.id, imageId), {
            onSuccess: () => {
                invalidate();
                toast.success(t("features.pets.edit.saveSuccess"));
            },
        });
        setDeletingImageId(null);
    };

    return (
        <div className="flex flex-col gap-8">
            <section className="flex flex-col gap-3">
                <RowLabel
                    label={t("features.pets.create.steps.media.imagesLabel")}
                    className="mt-2 md:mt-4"
                />

                <div className="grid grid-cols-3 md:grid-cols-4 gap-2">
                    {galleryImages.length > 0 &&
                        galleryImages.map((image) => (
                            <div
                                key={image.id}
                                className="relative aspect-square rounded-sm overflow-hidden bg-muted border"
                            >
                                <Image
                                    src={image.url}
                                    alt={pet.name}
                                    fill
                                    className="object-cover"
                                />
                                <button
                                    type="button"
                                    disabled={deletingImageId === image.id}
                                    onClick={() => handleDeleteImage(image.id)}
                                    className="absolute top-1 end-1 rounded-full bg-card p-1 border hover:bg-destructive/10 transition-colors disabled:opacity-50"
                                >
                                    <TrashBinTrash className="size-4 text-destructive" />
                                </button>
                            </div>
                        ))}
                    <ImagePickerDialog
                        value={newImageFiles}
                        onChange={setNewImageFiles}
                        maxFiles={15}
                        mode={isMobile ? "direct" : "dialog"}
                        className={cn(galleryImages.length === 0 && "col-span-3 md:col-span-4")}
                    >
                        <div
                            className={cn(
                                "gap-2 rounded-sm border-2 bg-muted/50 border-dashed w-full p-2 cursor-pointer flex md:flex-col items-center justify-center text-sm font-semibold hover:bg-muted/70 transition-colors aspect-square",
                                galleryImages.length === 0 &&
                                    "aspect-video col-span-3 md:col-span-4 w-full",
                            )}
                        >
                            {newImageFiles.length === 0 ? (
                                <>
                                    <GalleryAdd className="size-6" />
                                    <span
                                        className={cn(
                                            galleryImages.length > 0 && "hidden md:block",
                                        )}
                                    >
                                        {t("features.pets.create.steps.media.addImages")}
                                    </span>
                                </>
                            ) : (
                                <div className="grid grid-cols-3 gap-2 w-full">
                                    {newImageFiles.map((file) => (
                                        <div
                                            key={`${file.name}-${file.size}`}
                                            className="relative aspect-square rounded-sm bg-muted overflow-hidden"
                                        >
                                            <FilePreview file={file} alt={file.name} />
                                            <button
                                                type="button"
                                                className="absolute top-1 end-1 rounded-full bg-card/90 p-1 border"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    removeNewFile(file);
                                                }}
                                            >
                                                <X className="size-3" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </ImagePickerDialog>
                </div>

                {newImageFiles.length > 0 && (
                    <div className="fixed bottom-0 inset-x-0 p-4 bg-background border-t z-10 md:static md:p-0 md:border-0 md:bg-transparent md:mt-2">
                        <Button
                            onClick={handleAddImages}
                            disabled={isLoading}
                            size="xl"
                            className="w-full"
                        >
                            {t("common.actions.save")}
                        </Button>
                    </div>
                )}
            </section>
        </div>
    );
}
