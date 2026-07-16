"use client";

import Image from "next/image";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { Loader2, Trash2 } from "lucide-react";
import { Gallery, GallerySend } from "@solar-icons/react";

import {
    ActivityModel,
    addActivityImages,
    deleteActivityImage,
    uploadActivityAvatar,
} from "@workspace/modules/activities";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { cn } from "@workspace/ui/lib/utils";

import { useAsyncState } from "@/hooks/use-async-state";
import { useIsMobile } from "@/hooks/use-mobile";
import { ImagePickerDialog } from "@/components/forms/image-picker-dialog";
import { imageCompressionService } from "@/services/image-compression.service";
import { activityQueryKey } from "../hooks/use-activity";

const SECTION_HEADER_CLASS = "text-sm font-medium text-muted-foreground uppercase tracking-wider";

async function compressAvatar(file: File): Promise<File> {
    const compressed = await imageCompressionService.compressImage(file, {
        maxWidth: 800,
        maxHeight: 800,
        quality: 0.82,
    });
    const baseName = file.name.replace(/\.[^.]+$/, "");
    return new File([compressed.blob], `${baseName}.${compressed.format}`, {
        type: `image/${compressed.format}`,
    });
}

export function ActivityMediaManager({ activity }: { activity: ActivityModel }) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const isMobile = useIsMobile();
    const { execute: runAvatar, isLoading: isAvatarLoading } = useAsyncState();
    const { execute: runImages, isLoading: isImagesLoading } = useAsyncState();
    const { execute: runDelete, isLoading: isDeleting } = useAsyncState();

    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: activityQueryKey(activity.id) });

    const handleAvatarChange = async (file: File | null) => {
        if (!file) {
            return;
        }

        await runAvatar(
            async () => {
                const compressed = await compressAvatar(file);
                return uploadActivityAvatar(activity.id, compressed);
            },
            { displayError: true, onSuccess: refresh },
        );
    };

    const handleAddImages = async (files: File[]) => {
        if (files.length === 0) {
            return;
        }

        await runImages(() => addActivityImages(activity.id, files), {
            displayError: true,
            onSuccess: refresh,
        });
    };

    const handleDeleteImage = (imageId: string) =>
        runDelete(() => deleteActivityImage(activity.id, imageId), {
            displayError: true,
            onSuccess: refresh,
        });

    const avatarUrl = activity.avatarUrl;
    const isBusy = isAvatarLoading || isImagesLoading || isDeleting;

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className={SECTION_HEADER_CLASS}>
                    {t("features.activities.detail.sections.media")}
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-6">
                <Field className="gap-2">
                    <FieldLabel className="text-sm font-semibold">
                        <Gallery className="size-5" />
                        {t("features.become-host.steps.media.avatarLabel")}
                    </FieldLabel>
                    <ImagePickerDialog
                        value={[]}
                        onChange={(files) => handleAvatarChange(files[0] ?? null)}
                        maxFiles={1}
                        mode="direct"
                    >
                        <div className="relative w-full max-w-xs aspect-video rounded-2xl overflow-hidden border bg-muted/50 cursor-pointer flex items-center justify-center">
                            {avatarUrl ? (
                                <Image
                                    src={avatarUrl}
                                    alt={activity.name}
                                    fill
                                    className={cn(
                                        "object-cover transition-opacity",
                                        isAvatarLoading && "opacity-50",
                                    )}
                                />
                            ) : (
                                <span className="flex flex-col items-center gap-2 text-sm font-semibold text-muted-foreground">
                                    <GallerySend className="size-5" />
                                    {t("common.actions.addPhoto")}
                                </span>
                            )}
                        </div>
                    </ImagePickerDialog>
                </Field>

                <Field className="gap-2">
                    <FieldLabel className="text-sm font-semibold">
                        <GallerySend className="size-5" />
                        {t("features.become-host.steps.media.imagesLabel")}
                    </FieldLabel>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        {activity.images.map((image) => (
                            <div
                                key={image.id}
                                className="relative aspect-square rounded-lg overflow-hidden border bg-muted"
                            >
                                <Image
                                    src={image.url}
                                    alt={activity.name}
                                    fill
                                    className="object-cover"
                                />
                                <button
                                    type="button"
                                    disabled={isDeleting}
                                    className="absolute top-1.5 end-1.5 rounded-full bg-card/90 p-1 border hover:bg-destructive hover:text-destructive-foreground transition-colors disabled:opacity-50"
                                    onClick={() => handleDeleteImage(image.id)}
                                >
                                    <Trash2 className="size-3.5" />
                                </button>
                            </div>
                        ))}

                        <ImagePickerDialog
                            value={[]}
                            onChange={handleAddImages}
                            mode={isMobile ? "direct" : "dialog"}
                            maxFiles={15}
                        >
                            <div className="aspect-square rounded-lg border-2 border-dashed bg-muted/50 cursor-pointer flex flex-col items-center justify-center gap-1.5 text-xs font-semibold text-muted-foreground hover:bg-muted transition-colors">
                                <GallerySend className="size-5" />
                                {t("features.become-host.steps.media.pickImages")}
                            </div>
                        </ImagePickerDialog>
                    </div>
                </Field>

                {isBusy && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 className="size-4 animate-spin" />
                        {t("common.actions.loading")}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
