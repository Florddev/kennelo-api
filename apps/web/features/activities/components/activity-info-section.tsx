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
    updateActivity,
    deleteActivity,
    updateActivitySchema,
    ActivityModel,
    type UpdateActivityInput,
} from "@workspace/modules/activities";

import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";
import { useActivity, activityQueryKey } from "../hooks/use-activity";

const SECTION_HEADER_CLASS = "text-sm font-medium text-muted-foreground uppercase tracking-wider";

const T_SECTIONS_DETAILS = "features.activities.detail.sections.details" as const;
const T_SECTIONS_CONTACT = "features.activities.detail.sections.contact" as const;
const T_SECTIONS_ADDRESS = "features.activities.detail.sections.address" as const;
const T_SECTIONS_BUSINESS = "features.activities.detail.sections.business" as const;

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

function ViewMode({ activity, t }: { activity: ActivityModel; t: Translator }) {
    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className={SECTION_HEADER_CLASS}>{t(T_SECTIONS_DETAILS)}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-1">
                    <InfoItem
                        icon={Building2}
                        label={t("common.fields.activityName")}
                        value={activity.name}
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
                                !activity.description && "text-muted-foreground italic",
                            )}
                        >
                            {activity.description ||
                                t("features.activities.detail.empty.description")}
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
                        value={activity.phone}
                        empty={t("features.activities.detail.empty.phone")}
                    />
                    <Separator />
                    <InfoItem
                        icon={Mail}
                        label={t("common.fields.email")}
                        value={activity.email}
                        empty={t("features.activities.detail.empty.email")}
                    />
                    <Separator />
                    <InfoItem
                        icon={Globe}
                        label={t("common.fields.website")}
                        value={activity.website}
                        empty={t("features.activities.detail.empty.website")}
                    />
                </CardContent>
            </Card>

            {activity.address && (
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
                            value={activity.address.getFullAddress()}
                            empty=""
                        />
                    </CardContent>
                </Card>
            )}

            {activity.siret && (
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
                            value={activity.siret}
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

function buildEditDefaults(e: ActivityModel): UpdateActivityInput {
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
    activity,
    onCancel,
    onUpdated,
}: {
    activity: ActivityModel;
    onCancel: () => void;
    onUpdated: (updated: ActivityModel) => void;
}) {
    const t = useTranslations();
    const { execute, isLoading } = useAsyncState();

    const { control, handleSubmit, setError } = useForm<UpdateActivityInput>({
        resolver: zodResolver(updateActivitySchema),
        defaultValues: buildEditDefaults(activity),
    });

    const onSubmit = async (data: UpdateActivityInput) => {
        const result = await execute(() => updateActivity(activity.id, data), {
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
                        label={t("common.fields.activityName")}
                        placeholder={t("common.placeholders.activityName")}
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
                    variant="destructive"
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
                        {t("features.activities.detail.deleteTitle")}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t("features.activities.detail.deleteDescription")}
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

export function ActivityInfoSection({ activityId, t }: { activityId: string; t: Translator }) {
    const { activity, isLoading } = useActivity(activityId);
    const { refreshUser } = useAuth();
    const { routes, router } = useNavigation();
    const queryClient = useQueryClient();
    const [isEditing, setIsEditing] = useState(false);
    const { execute: runDelete, isLoading: isDeleting } = useAsyncState();

    const handleUpdated = async (updated: ActivityModel) => {
        queryClient.setQueryData(activityQueryKey(activityId), updated);
        setIsEditing(false);
        await refreshUser();
    };

    const handleDelete = () =>
        runDelete(() => deleteActivity(activityId), {
            onSuccess: async () => {
                await queryClient.invalidateQueries({ queryKey: ["activities", "list"] });
                await refreshUser();
                router.push(routes.MyActivities());
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

    if (!activity) {
        return (
            <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <Building2 className="size-12 text-muted-foreground" />
                <h2 className="text-xl font-semibold">
                    {t("features.activities.detail.notFound")}
                </h2>
                <p className="text-muted-foreground">
                    {t("features.activities.detail.notFoundDescription")}
                </p>
            </div>
        );
    }

    const statusKey = activity.isActive ? "active" : "inactive";

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-bold tracking-tight">
                        {isEditing ? t("features.activities.detail.editTitle") : activity.name}
                    </h1>
                    <Badge variant="outline" className={cn(STATUS_BADGE_CLASS[statusKey])}>
                        {t(`features.activities.status.${statusKey}`)}
                    </Badge>
                </div>
                {!isEditing && (
                    <div className="flex items-center gap-1">
                        <Button
                            variant="flat"
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
                    activity={activity}
                    onCancel={() => setIsEditing(false)}
                    onUpdated={handleUpdated}
                />
            ) : (
                <ViewMode activity={activity} t={t} />
            )}
        </div>
    );
}
