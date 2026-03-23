"use client";

import { UseFormWatch } from "react-hook-form";
import { useTranslations } from "next-intl";
import { Pencil, Building2, Phone, MapPin, FileText, AlertCircle, Info } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";

type ReviewStepProps = {
    watch: UseFormWatch<CreateEstablishmentInput>;
    onGoTo: (stepId: string) => void;
    error?: string;
    hostType: "professional" | "individual" | null;
};

function ReviewSection({
    title,
    icon: Icon,
    onEdit,
    children,
}: {
    title: string;
    icon: React.ElementType;
    onEdit?: () => void;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-2xl border bg-card overflow-hidden">
            <div className="flex items-center justify-between px-5 py-3.5 border-b">
                <h3 className="text-sm font-semibold flex items-center gap-2">
                    <Icon className="size-4 text-muted-foreground" />
                    {title}
                </h3>
                {onEdit && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-sm text-primary hover:text-primary gap-1.5 rounded-4xl"
                        onClick={onEdit}
                    >
                        <Pencil className="size-3" />
                    </Button>
                )}
            </div>
            <div className="p-5 space-y-2">{children}</div>
        </div>
    );
}

function ReviewItem({ label, value }: { label: string; value: string | undefined }) {
    if (!value) return null;
    return (
        <div className="flex justify-between text-sm py-1">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-foreground font-medium text-end max-w-[60%] truncate">
                {value}
            </span>
        </div>
    );
}

export function ReviewStep({ watch, onGoTo, error, hostType }: ReviewStepProps) {
    const t = useTranslations();
    const values = watch();

    const isIndividual = hostType === "individual";

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.review.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.review.subtitle")}
                </p>
            </div>

            {error && (
                <div className="flex items-start gap-3 rounded-2xl border border-destructive/30 bg-destructive/5 p-4">
                    <AlertCircle className="size-5 text-destructive shrink-0 mt-0.5" />
                    <p className="text-sm text-destructive font-medium">{error}</p>
                </div>
            )}

            <div className="flex flex-col gap-4">
                {isIndividual ? (
                    <div className="flex items-start gap-3 rounded-2xl border border-primary/30 bg-primary/5 p-4">
                        <Info className="size-5 text-primary shrink-0 mt-0.5" />
                        <div className="flex flex-col gap-1">
                            <p className="text-sm font-medium">
                                {t("features.become-host.steps.review.autoFilledFromProfile")}
                            </p>
                            <p className="text-sm text-muted-foreground">{values.name}</p>
                        </div>
                    </div>
                ) : (
                    <ReviewSection
                        title={t("features.become-host.steps.establishmentInfo.title")}
                        icon={Building2}
                        onEdit={() => onGoTo("establishment-info")}
                    >
                        <ReviewItem
                            label={t("common.fields.establishmentName")}
                            value={values.name}
                        />
                        <ReviewItem
                            label={t("common.fields.description")}
                            value={values.description}
                        />
                    </ReviewSection>
                )}

                <ReviewSection
                    title={t("features.become-host.steps.contactDetails.title")}
                    icon={Phone}
                    onEdit={() => onGoTo("contact-details")}
                >
                    <ReviewItem label={t("common.fields.phone")} value={values.phone} />
                    <ReviewItem label={t("common.fields.email")} value={values.email} />
                    <ReviewItem label={t("common.fields.website")} value={values.website} />
                </ReviewSection>

                <ReviewSection
                    title={t("features.become-host.steps.address.title")}
                    icon={MapPin}
                    onEdit={() => onGoTo("address")}
                >
                    <ReviewItem
                        label={t("common.fields.addressLine1")}
                        value={values.address?.line1}
                    />
                    <ReviewItem
                        label={t("common.fields.addressLine2")}
                        value={values.address?.line2}
                    />
                    <ReviewItem label={t("common.fields.city")} value={values.address?.city} />
                    <ReviewItem
                        label={t("common.fields.postalCode")}
                        value={values.address?.postalCode}
                    />
                    <ReviewItem label={t("common.fields.region")} value={values.address?.region} />
                    <ReviewItem
                        label={t("common.fields.country")}
                        value={values.address?.country}
                    />
                </ReviewSection>

                {!isIndividual && (
                    <ReviewSection
                        title={t("features.become-host.steps.businessInfo.title")}
                        icon={FileText}
                        onEdit={() => onGoTo("business-info")}
                    >
                        <ReviewItem label={t("common.fields.siret")} value={values.siret} />
                    </ReviewSection>
                )}
            </div>
        </div>
    );
}
