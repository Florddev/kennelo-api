"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { Building2, Phone, Mail, Globe, MapPin, FileText, Pencil, Trash2 } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { Separator } from "@workspace/ui/components/separator";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@workspace/ui/components/tabs";
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
import { KHome, KCompass, KCalendar, KHeart } from "@workspace/ui/components/icons";
import {
    getEstablishment,
    updateEstablishment,
    deleteEstablishment,
    EstablishmentModel,
    updateEstablishmentSchema,
    type UpdateEstablishmentInput,
} from "@workspace/modules/establishments";
import { useAuth } from "@/features/auth";
import { DashboardTab } from "@/features/establishments/components/dashboard-tab";
import { CapacitiesTab } from "@/features/establishments/components/capacities-tab";
import { AvailabilitiesTab } from "@/features/establishments/components/availabilities-tab";
import { useNavigation } from "@/hooks/use-navigation";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";

const SECTION_HEADER_CLASS = "text-sm font-medium text-muted-foreground uppercase tracking-wider";
const T_SECTIONS_DETAILS = "features.my-establishments.detail.sections.details" as const;
const T_SECTIONS_CONTACT = "features.my-establishments.detail.sections.contact" as const;
const T_SECTIONS_ADDRESS = "features.my-establishments.detail.sections.address" as const;
const T_SECTIONS_BUSINESS = "features.my-establishments.detail.sections.business" as const;

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

function EstablishmentViewMode({
    establishment,
    t,
}: {
    establishment: EstablishmentModel;
    t: ReturnType<typeof useTranslations>;
}) {
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

type EditFieldProps = {
    control: ReturnType<typeof useForm<UpdateEstablishmentInput>>["control"];
    isLoading: boolean;
};

function EditDetailsSection({ control, isLoading }: EditFieldProps) {
    const t = useTranslations();
    return (
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
    );
}

function EditContactSection({ control, isLoading }: EditFieldProps) {
    const t = useTranslations();
    return (
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
    );
}

function EditAddressSection({ control, isLoading }: EditFieldProps) {
    const t = useTranslations();
    return (
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
    );
}

function orEmpty(val: string | null | undefined): string {
    return val || "";
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

function EstablishmentEditForm({
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
            <EditDetailsSection control={control} isLoading={isLoading} />
            <EditContactSection control={control} isLoading={isLoading} />
            <EditAddressSection control={control} isLoading={isLoading} />

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

function EstablishmentNotFound({ backHref }: { backHref: string }) {
    const t = useTranslations();
    return (
        <div className="container mx-auto px-4 py-16 max-w-5xl text-center">
            <Building2 className="size-12 text-muted-foreground mx-auto mb-4" />
            <h2 className="text-xl font-semibold mb-2">
                {t("features.my-establishments.detail.notFound")}
            </h2>
            <p className="text-muted-foreground mb-6">
                {t("features.my-establishments.detail.notFoundDescription")}
            </p>
            <Button asChild variant="outline" className="rounded-4xl">
                <Link href={backHref}>{t("common.actions.back")}</Link>
            </Button>
        </div>
    );
}

function DeleteEstablishmentDialog({
    onDelete,
    isDeleting,
}: {
    onDelete: () => void;
    isDeleting: boolean;
}) {
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

function DetailPageHeader({
    establishment,
    isEditing,
    isDeleting,
    backHref,
    onEdit,
    onDelete,
}: {
    establishment: EstablishmentModel;
    isEditing: boolean;
    isDeleting: boolean;
    backHref: string;
    onEdit: () => void;
    onDelete: () => void;
}) {
    const t = useTranslations();
    return (
        <div className="flex items-center justify-between mb-8">
            <div>
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    className="gap-1.5 -ms-2 mb-2 text-muted-foreground"
                >
                    <Link href={backHref}>{t("common.actions.back")}</Link>
                </Button>
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-bold tracking-tight">
                        {isEditing
                            ? t("features.my-establishments.detail.editTitle")
                            : establishment.name}
                    </h1>
                    <Badge variant={establishment.isActive ? "default" : "secondary"}>
                        {establishment.isActive
                            ? t("features.my-establishments.status.active")
                            : t("features.my-establishments.status.inactive")}
                    </Badge>
                </div>
                {isEditing && (
                    <p className="text-sm text-muted-foreground mt-1">
                        {t("features.my-establishments.detail.editDescription")}
                    </p>
                )}
            </div>
            {!isEditing && (
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-4xl gap-1.5"
                        onClick={onEdit}
                    >
                        <Pencil className="size-3.5" />
                        {t("common.actions.edit")}
                    </Button>
                    <DeleteEstablishmentDialog onDelete={onDelete} isDeleting={isDeleting} />
                </div>
            )}
        </div>
    );
}

export default function EstablishmentDetailPage() {
    const t = useTranslations();
    const { isLoaded, isAuthenticated, refreshUser } = useAuth();
    const { routes, router, params } = useNavigation<{ id: string }>();
    const [establishment, setEstablishment] = useState<EstablishmentModel | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const { execute: executeDelete, isLoading: isDeleting } = useAsyncState();

    useEffect(() => {
        if (isLoaded && !isAuthenticated) {
            router.push(routes.Login());
        }
    }, [isLoaded, isAuthenticated, router, routes]);

    useEffect(() => {
        if (!isLoaded || !isAuthenticated || !params.id) return;
        setIsLoading(true);
        getEstablishment(params.id)
            .then(setEstablishment)
            .catch(() => setNotFound(true))
            .finally(() => setIsLoading(false));
    }, [isLoaded, isAuthenticated, params.id]);

    const handleUpdated = async (updated: EstablishmentModel) => {
        setEstablishment(updated);
        setIsEditing(false);
        await refreshUser();
    };

    const handleDelete = async () => {
        if (!establishment) return;
        const result = await executeDelete(() => deleteEstablishment(establishment.id));
        if (result !== undefined) {
            await refreshUser();
            router.push(routes.MyEstablishments());
        }
    };

    if (!isLoaded || !isAuthenticated || isLoading) {
        return null;
    }

    if (notFound || !establishment) {
        return <EstablishmentNotFound backHref={routes.MyEstablishments()} />;
    }

    return (
        <div className="container mx-auto px-4 py-8 max-w-5xl">
            <DetailPageHeader
                establishment={establishment}
                isEditing={isEditing}
                isDeleting={isDeleting}
                backHref={routes.MyEstablishments()}
                onEdit={() => setIsEditing(true)}
                onDelete={handleDelete}
            />

            {isEditing ? (
                <EstablishmentEditForm
                    establishment={establishment}
                    onCancel={() => setIsEditing(false)}
                    onUpdated={handleUpdated}
                />
            ) : (
                <Tabs defaultValue="overview" orientation="horizontal" className="flex flex-col">
                    <TabsList variant="line" className="border-b w-full justify-start gap-0">
                        <TabsTrigger value="overview" className="gap-2 px-4 py-2.5">
                            <KHome size={18} secondary="text-muted-foreground" />
                            {t(T_SECTIONS_DETAILS)}
                        </TabsTrigger>
                        <TabsTrigger value="dashboard" className="gap-2 px-4 py-2.5">
                            <KCompass size={18} secondary="text-muted-foreground" />
                            {t("features.my-establishments.dashboard.title")}
                        </TabsTrigger>
                        <TabsTrigger value="capacities" className="gap-2 px-4 py-2.5">
                            <KHeart size={18} secondary="text-muted-foreground" />
                            {t("features.my-establishments.capacities.title")}
                        </TabsTrigger>
                        <TabsTrigger value="availabilities" className="gap-2 px-4 py-2.5">
                            <KCalendar size={18} secondary="text-muted-foreground" />
                            {t("features.my-establishments.availabilities.title")}
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="overview" className="pt-6">
                        <EstablishmentViewMode establishment={establishment} t={t} />
                    </TabsContent>
                    <TabsContent value="dashboard" className="pt-6">
                        <DashboardTab establishmentId={establishment.id} />
                    </TabsContent>
                    <TabsContent value="capacities" className="pt-6">
                        <CapacitiesTab establishmentId={establishment.id} />
                    </TabsContent>
                    <TabsContent value="availabilities" className="pt-6">
                        <AvailabilitiesTab establishmentId={establishment.id} />
                    </TabsContent>
                </Tabs>
            )}
        </div>
    );
}
