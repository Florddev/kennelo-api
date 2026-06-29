import { useEffect, useMemo } from "react";
import { useTranslations } from "next-intl";
import { X } from "lucide-react";
import { type CreatePetInput } from "@workspace/modules/pets";
import { Control } from "react-hook-form";
import { DocumentMedicine, Gallery, GallerySend, Men, TextSquare, Women } from "@solar-icons/react";

import { Field, FieldLabel } from "@workspace/ui/components/field";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InlineController } from "@/components/forms/inline-controller";
import { ImagePickerDialog } from "@/components/forms/image-picker-dialog";
import { BreedSelectField } from "@/features/pets/components/forms/breed-select-field";

function FilePreview({ file, alt }: { file: File; alt: string }) {
    const url = useMemo(() => URL.createObjectURL(file), [file]);
    useEffect(() => () => URL.revokeObjectURL(url), [url]);
    // eslint-disable-next-line @next/next/no-img-element
    return <img src={url} alt={alt} className="size-full object-cover" />;
}

export function IdentityStep({
    control,
    isLoading,
    avatarFile,
    onAvatarChange,
    animalTypeId,
}: {
    control: Control<CreatePetInput>;
    isLoading: boolean;
    avatarFile: File | null;
    onAvatarChange: (file: File | null) => void;
    animalTypeId: string | null | undefined;
}) {
    const t = useTranslations();

    return (
        <WizardStepShell title={t("features.pets.create.steps.identity.title")}>
            {/* <div className="flex flex-col justify-center items-center gap-2 p-3">

                <div className="relative flex justify-center items-center bg-muted/50 border-2 border-dashed size-24 rounded-full">
                    <Camera className="size-8 text-muted-foreground" />

                    <Button variant="default" size="icon-xs" className="absolute right-0 bottom-0">
                        <Plus className="size-4" />
                    </Button>
                </div>

                <div className="flex gap-2 text-sm md:text-base font-semibold">
                    Ajouter une photo 
                </div>
            </div> */}

            <Field className="gap-2">
                <FieldLabel className="text-sm md:text-base font-semibold">
                    <Gallery className="size-5" />
                    {t("features.pets.create.steps.media.avatarLabel")}
                </FieldLabel>
                <ImagePickerDialog
                    value={avatarFile ? [avatarFile] : []}
                    onChange={(files) => onAvatarChange(files[0] ?? null)}
                    maxFiles={1}
                    mode="direct"
                >
                    <div className="gap-2 rounded-sm border-2 bg-muted/50 border-dashed min-h-42 w-full p-2 h-fit cursor-pointer flex items-center justify-center text-sm md:text-base font-semibold shadow-sm">
                        {!avatarFile ? (
                            <>
                                <GallerySend className="size-5" />
                                {t("common.actions.addPhoto")}
                            </>
                        ) : (
                            <div className="relative w-full aspect-video rounded-2xl overflow-hidden border bg-muted">
                                <FilePreview file={avatarFile} alt={avatarFile.name} />
                                <button
                                    type="button"
                                    className="absolute top-1 end-1 rounded-full bg-card/90 p-1 border"
                                    onClick={(event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        onAvatarChange(null);
                                    }}
                                >
                                    <X className="size-3" />
                                </button>
                            </div>
                        )}
                    </div>
                </ImagePickerDialog>
            </Field>

            <InlineController
                name="name"
                control={control}
                type="text"
                label={t("features.pets.fields.name")}
                Icon={TextSquare}
                // placeholder={t("features.pets.create.placeholders.name")}
                isLoading={isLoading}
                className="shadow-xs border-border/80"
            />

            <InlineController
                name="sex"
                control={control}
                type="button-list"
                label={t("features.pets.fields.sex")}
                Icon={DocumentMedicine}
                isLoading={isLoading}
                options={[
                    { label: t("features.pets.sex.male"), value: "male", Icon: Men },
                    { label: t("features.pets.sex.female"), value: "female", Icon: Women },
                ]}
                className="shadow-xs border-border/80"
            />

            <BreedSelectField
                control={control}
                animalTypeId={animalTypeId}
                isLoading={isLoading}
                className="w-full shadow-xs border-border/80"
            />
        </WizardStepShell>
    );
}
