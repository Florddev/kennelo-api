"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { Building2, Phone, Mail, Globe, MapPin, FileText, Pencil, Trash2 } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { Badge } from "@workspace/ui/components/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Separator } from "@workspace/ui/components/separator";
import { Skeleton } from "@workspace/ui/components/skeleton";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@workspace/ui/components/alert-dialog";
import { cn } from "@workspace/ui/lib/utils";
import {
    updateEstablishment,
    deleteEstablishment,
    updateEstablishmentSchema,
    EstablishmentModel,
    type UpdateEstablishmentInput,
} from "@workspace/modules/establishments";

import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";
import { useEstablishment, establishmentQueryKey } from "../hooks/use-establishment";

const SECTION_HEADER_CLASS = "text-sm font-medium text-muted-foreground uppercase tracking-wider";

const T_SECTIONS_DETAILS = "features.my-establishments.detail.sections.details" as const;
const T_SECTIONS_CONTACT = "features.my-establishments.detail.sections.contact" as const;
const T_SECTIONS_ADDRESS = "features.my-establishments.detail.sections.address" as const;
const T_SECTIONS_BUSINESS = "features.my-establishments.detail.sections.business" as const;

const STATUS_BADGE_CLASS = {
    active: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/40",
    inactive: "bg-zinc-500/15 text-zinc-700 dark:text-zinc-300 border-zinc-500/40",
} as const;

type Translator = ReturnType<typeof useTranslations>;

function InfoItem({
    icon: Icon,
    label,
    value,
    empty,
}: {
    icon: React.ElementType;
    label: string;
    value: string | null | undefined;
    empty: string;
}) {
    return (
        <div className="flex items-start gap-3 py-3">
            <div className="flex items-center justify-center size-8 rounded-xl bg-muted shrink-0 mt-0.5">
                <Icon className="size-4 text-muted-foreground" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-xs text-muted-foreground">{label}</p>
                <p
                    className={cn(
                        "text-sm font-medium mt-0.5",
                        !value && "text-muted-foreground italic",
                    )}
                >
                    {value || empty}
                </p>
            </div>
        </div>
    );
}

function ViewMode({ establishment, t }: { establishment: EstablishmentModel; t: Translator }) {
    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_DETAILS)}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-1">
                    <InfoItem
                        icon={Building2}
                        label={t("common.fields.establishmentName")}
                        value={establishment.name}
                        empty=""
                    />
                    <Separator />
                    <div className="py-3">
                        <p className="text-xs text-muted-foreground mb-1.5">
                            {t("common.fields.description")}
                        </p>
                        <p
                            className={cn(
                                "text-sm leading-relaxed",
                                !establishment.description && "text-muted-foreground italic",
                            )}
                        >
                            {establishment.description ||
                                t("features.my-establishments.detail.empty.description")}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_CONTACT)}</CardTitle>
                </CardHeader>
                <CardContent>
                    <InfoItem
                        icon={Phone}
                        label={t("common.fields.phone")}
                        value={establishment.phone}
                        empty={t("features.my-establishments.detail.empty.phone")}
                    />
                    <Separator />
                    <InfoItem
                        icon={Mail}
                        label={t("common.fields.email")}
                        value={establishment.email}
                        empty={t("features.my-establishments.detail.empty.email")}
                    />
                    <Separator />
                    <InfoItem
                        icon={Globe}
                        label={t("common.fields.website")}
                        value={establishment.website}
                        empty={t("features.my-establishments.detail.empty.website")}
                    />
                </CardContent>
            </Card>

            {establishment.address && (
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className={SECTION_HEADER_CLASS}>
                            {t(T_SECTIONS_ADDRESS)}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <InfoItem
                            icon={MapPin}
                            label={t(T_SECTIONS_ADDRESS)}
                            value={establishment.address.getFullAddress()}
                            empty=""
                        />
                    </CardContent>
                </Card>
            )}

            {establishment.siret && (
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className={SECTION_HEADER_CLASS}>
                            {t(T_SECTIONS_BUSINESS)}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <InfoItem
                            icon={FileText}
                            label={t("common.fields.siret")}
                            value={establishment.siret}
                            empty=""
                        />
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

function orEmpty(value: string | null | undefined): string {
    return value || "";
}

function buildEditDefaults(e: EstablishmentModel): UpdateEstablishmentInput {
    return {
        name: e.name,
        description: orEmpty(e.description),
        phone: orEmpty(e.phone),
        email: orEmpty(e.email),
        website: orEmpty(e.website),
        siret: orEmpty(e.siret),
        address: {
            line1: orEmpty(e.address?.line1),
            line2: orEmpty(e.address?.line2),
            city: orEmpty(e.address?.city),
            postalCode: orEmpty(e.address?.postalCode),
            region: orEmpty(e.address?.region),
            country: orEmpty(e.address?.country),
        },
    };
}

function EditForm({
    establishment,
    onCancel,
    onUpdated,
}: {
    establishment: EstablishmentModel;
    onCancel: () => void;
    onUpdated: (updated: EstablishmentModel) => void;
}) {
    const t = useTranslations();
    const { execute, isLoading } = useAsyncState();

    const { control, handleSubmit, setError } = useForm<UpdateEstablishmentInput>({
        resolver: zodResolver(updateEstablishmentSchema),
        defaultValues: buildEditDefaults(establishment),
    });

    const onSubmit = async (data: UpdateEstablishmentInput) => {
        const result = await execute(() => updateEstablishment(establishment.id, data), {
            setFieldError: setError,
        });
        if (result) {
            onUpdated(result);
        }
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_DETAILS)}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <InputController
                        name="name"
                        control={control}
                        label={t("common.fields.establishmentName")}
                        placeholder={t("common.placeholders.establishmentName")}
                        isLoading={isLoading}
                    />
                    <TextareaController
                        name="description"
                        control={control}
                        label={t("common.fields.description")}
                        placeholder={t("common.placeholders.description")}
                        isLoading={isLoading}
                        rows={4}
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_CONTACT)}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <InputController
                        name="phone"
                        control={control}
                        label={t("common.fields.phone")}
                        placeholder={t("common.placeholders.phone")}
                        isLoading={isLoading}
                        type="phone"
                    />
                    <InputController
                        name="email"
                        control={control}
                        label={t("common.fields.email")}
                        placeholder={t("common.placeholders.email")}
                        isLoading={isLoading}
                        type="email"
                    />
                    <InputController
                        name="website"
                        control={control}
                        label={t("common.fields.website")}
                        placeholder={t("common.placeholders.website")}
                        isLoading={isLoading}
                        type="url"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_ADDRESS)}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <InputController
                        name="address.line1"
                        control={control}
                        label={t("common.fields.addressLine1")}
                        placeholder={t("common.placeholders.addressLine1")}
                        isLoading={isLoading}
                    />
                    <InputController
                        name="address.line2"
                        control={control}
                        label={t("common.fields.addressLine2")}
                        placeholder={t("common.placeholders.addressLine2")}
                        isLoading={isLoading}
                    />
                    <div className="grid grid-cols-2 gap-4">
                        <InputController
                            name="address.city"
                            control={control}
                            label={t("common.fields.city")}
                            placeholder={t("common.placeholders.city")}
                            isLoading={isLoading}
                        />
                        <InputController
                            name="address.postalCode"
                            control={control}
                            label={t("common.fields.postalCode")}
                            placeholder={t("common.placeholders.postalCode")}
                            isLoading={isLoading}
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <InputController
                            name="address.region"
                            control={control}
                            label={t("common.fields.region")}
                            placeholder={t("common.placeholders.region")}
                            isLoading={isLoading}
                        />
                        <InputController
                            name="address.country"
                            control={control}
                            label={t("common.fields.country")}
                            placeholder={t("common.placeholders.country")}
                            isLoading={isLoading}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_BUSINESS)}</CardTitle>
                </CardHeader>
                <CardContent>
                    <InputController
                        name="siret"
                        control={control}
                        label={t("common.fields.siret")}
                        placeholder={t("common.placeholders.siret")}
                        isLoading={isLoading}
                    />
                </CardContent>
            </Card>

            <div className="flex justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-4xl"
                    onClick={onCancel}
                    disabled={isLoading}
                >
                    {t("common.actions.cancel")}
                </Button>
                <Button type="submit" className="rounded-4xl" disabled={isLoading}>
                    {isLoading ? t("common.actions.loading") : t("common.actions.save")}
                </Button>
            </div>
        </form>
    );
}

function DeleteDialog({ onDelete, isDeleting }: { onDelete: () => void; isDeleting: boolean }) {
    const t = useTranslations();
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="rounded-4xl gap-1.5 text-destructive hover:text-destructive"
                >
                    <Trash2 className="size-3.5" />
                    {t("common.actions.delete")}
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {t("features.my-establishments.detail.deleteTitle")}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t("features.my-establishments.detail.deleteDescription")}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{t("common.actions.cancel")}</AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        onClick={onDelete}
                        disabled={isDeleting}
                    >
                        {isDeleting ? t("common.actions.loading") : t("common.actions.delete")}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export function EstablishmentInfoSection({
    establishmentId,
    t,
}: {
    establishmentId: string;
    t: Translator;
}) {
    const { establishment, isLoading } = useEstablishment(establishmentId);
    const { refreshUser } = useAuth();
    const { routes, router } = useNavigation();
    const queryClient = useQueryClient();
    const [isEditing, setIsEditing] = useState(false);
    const { execute: runDelete, isLoading: isDeleting } = useAsyncState();

    const handleUpdated = async (updated: EstablishmentModel) => {
        queryClient.setQueryData(establishmentQueryKey(establishmentId), updated);
        setIsEditing(false);
        await refreshUser();
    };

    const handleDelete = () =>
        runDelete(() => deleteEstablishment(establishmentId), {
            onSuccess: async () => {
                await queryClient.invalidateQueries({ queryKey: ["establishments", "list"] });
                await refreshUser();
                router.push(routes.MyEstablishments());
            },
        });

    if (isLoading) {
        return (
            <div className="flex flex-col gap-6">
                <Skeleton className="h-8 w-48" />
                <div className="grid gap-6 lg:grid-cols-2">
                    <Skeleton className="h-48 w-full rounded-2xl" />
                    <Skeleton className="h-48 w-full rounded-2xl" />
                </div>
            </div>
        );
    }

    if (!establishment) {
        return (
            <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <Building2 className="size-12 text-muted-foreground" />
                <h2 className="text-xl font-semibold">
                    {t("features.my-establishments.detail.notFound")}
                </h2>
                <p className="text-muted-foreground">
                    {t("features.my-establishments.detail.notFoundDescription")}
                </p>
            </div>
        );
    }

    const statusKey = establishment.isActive ? "active" : "inactive";

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-bold tracking-tight">
                        {isEditing
                            ? t("features.my-establishments.detail.editTitle")
                            : establishment.name}
                    </h1>
                    <Badge variant="outline" className={cn(STATUS_BADGE_CLASS[statusKey])}>
                        {t(`features.my-establishments.status.${statusKey}`)}
                    </Badge>
                </div>
                {!isEditing && (
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="rounded-4xl gap-1.5"
                            onClick={() => setIsEditing(true)}
                        >
                            <Pencil className="size-3.5" />
                            {t("common.actions.edit")}
                        </Button>
                        <DeleteDialog onDelete={handleDelete} isDeleting={isDeleting} />
                    </div>
                )}
            </div>

            {isEditing ? (
                <EditForm
                    establishment={establishment}
                    onCancel={() => setIsEditing(false)}
                    onUpdated={handleUpdated}
                />
            ) : (
                <ViewMode establishment={establishment} t={t} />
            )}
        </div>
    );
}
