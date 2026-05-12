import { X } from "lucide-react";
import { useEffect, useMemo } from "react";
import { useTranslations } from "next-intl";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { ImagePickerDialog } from "@/components/forms/image-picker-dialog";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { GallerySend } from "@solar-icons/react";

function FilePreview({ file, alt }: { file: File; alt: string }) {
    const url = useMemo(() => URL.createObjectURL(file), [file]);
    useEffect(() => () => URL.revokeObjectURL(url), [url]);
    // eslint-disable-next-line @next/next/no-img-element
    return <img src={url} alt={alt} className="size-full object-cover" />;
}

type MediaStepProps = {
    imageFiles: File[];
    onImageFilesChange: (files: File[]) => void;
};

export function MediaStep({ imageFiles, onImageFilesChange }: MediaStepProps) {
    const t = useTranslations();

    return (
        <WizardStepShell
            title={t("features.pets.create.steps.media.title")}
            subtitle={t("features.pets.create.steps.media.subtitle")}
        >
            <div className="flex flex-col gap-4">
                <Field className="gap-2">
                    <FieldLabel>{t("features.pets.create.steps.media.imagesLabel")}</FieldLabel>
                    <ImagePickerDialog
                        value={imageFiles}
                        onChange={onImageFilesChange}
                        maxFiles={15}
                    >
                        <div className="gap-2 rounded-sm border-2 bg-muted/50 border-dashed min-h-42 w-full p-2 h-fit cursor-pointer flex items-center justify-center text-sm md:text-base font-semibold">
                            {imageFiles.length === 0 ? (
                                <>
                                    <GallerySend className="size-4" />
                                    {t("features.pets.create.steps.media.pickImages")}
                                </>
                            ) : (
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    {imageFiles.map((file) => (
                                        <div
                                            key={`${file.name}-${file.size}`}
                                            className="relative aspect-square rounded-sm bg-muted overflow-hidden"
                                        >
                                            <FilePreview file={file} alt={file.name} />
                                            <button
                                                type="button"
                                                className="absolute top-1 end-1 rounded-full bg-card/90 p-1 border"
                                                onClick={(event) => {
                                                    event.preventDefault();
                                                    event.stopPropagation();
                                                    onImageFilesChange(
                                                        imageFiles.filter(
                                                            (currentFile) =>
                                                                !(
                                                                    currentFile.name ===
                                                                        file.name &&
                                                                    currentFile.size === file.size
                                                                ),
                                                        ),
                                                    );
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
                </Field>
            </div>
        </WizardStepShell>
    );
}
