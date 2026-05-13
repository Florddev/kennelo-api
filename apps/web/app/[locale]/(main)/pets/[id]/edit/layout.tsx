"use client";

import React from "react";
import { useParams, usePathname } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import Link from "next/link";
import Image from "next/image";
import {
    ArrowLeft,
    DocumentMedicine,
    Gallery,
    GalleryMinimalistic,
    InfoSquare,
    Star,
    TrashBinTrash,
} from "@solar-icons/react";
import { useHideBottomNavbar } from "@/hooks/use-hide-bottom-navbar";
import { useScrolled } from "@/hooks/use-scrolled";
import { NavRow } from "@/components/navigation/nav-row";
import { Separator } from "@workspace/ui/components/separator";
import { Card, CardContent } from "@workspace/ui/components/card";
import { Button } from "@workspace/ui/components/button";
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
import { useNavigation } from "@/hooks/use-navigation";
import { useRouter } from "next/navigation";
import { deletePet, PetModel } from "@workspace/modules/pets";
import { useAsyncState } from "@/hooks/use-async-state";
import { usePet } from "@/features/pets/hooks/use-pet";
import { toast } from "sonner";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { Progress } from "@workspace/ui/components/progress";

const EDIT_SECTIONS = ["general", "health", "personality", "photos"] as const;

function computeCompletion(pet: PetModel): number {
    const checks = [
        Boolean(pet.name),
        Boolean(pet.breed),
        Boolean(pet.sex),
        Boolean(pet.birthDate),
        pet.weight !== null,
        Boolean(pet.about),
        pet.isSterilized !== null,
        Boolean(pet.healthNotes),
        Boolean(pet.avatarUrl),
        (pet.attributes?.length ?? 0) > 0,
        pet.images.length > 0,
    ];
    return Math.round((checks.filter(Boolean).length / checks.length) * 100);
}

function PetAvatarCard({
    pet,
    avatarUrl,
    completion,
}: {
    pet: PetModel | null;
    avatarUrl: string | undefined;
    completion: number;
}) {
    const t = useTranslations();
    return (
        <div className="flex flex-col items-center gap-3 w-full">
            <div className="relative rounded-sm overflow-hidden bg-muted shadow-lg shrink-0 w-full aspect-14/9 2xl:aspect-video">
                {avatarUrl ? (
                    <Image src={avatarUrl} alt={pet?.name ?? ""} fill className="object-cover" />
                ) : (
                    <div className="size-full flex items-center justify-center">
                        <GalleryMinimalistic className="size-10 text-muted-foreground mb-12" />
                    </div>
                )}

                <div className="absolute p-1 w-full bottom-0 left-0">
                    <div className="bg-white/70 w-full rounded-2xl p-4 backdrop-blur-sm">
                        <Field className="w-full">
                            <FieldLabel htmlFor="progress-upload">
                                <span>{t("features.pets.edit.completionLabel")}</span>
                                <span className="ml-auto">{completion}%</span>
                            </FieldLabel>
                            {pet && (
                                <Progress
                                    value={completion}
                                    id="progress-upload"
                                    className="backdrop-blur-lg bg-transparent"
                                />
                            )}
                        </Field>
                    </div>
                </div>
            </div>
        </div>
    );
}

function DeletePetDialog({
    petName,
    isDeleting,
    onDelete,
}: {
    petName: string;
    isDeleting: boolean;
    onDelete: () => void;
}) {
    const t = useTranslations();
    const isMobile = useIsMobile();

    const trigger = (
        <NavRow
            icon={TrashBinTrash}
            label={t("features.pets.edit.delete")}
            destructive
            className="md:rounded-md md:p-4 hover:bg-destructive/10 w-full cursor-pointer"
        />
    );

    if (isMobile) {
        return (
            <Drawer>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>
                            {t("features.pets.edit.deleteTitle", { name: petName })}
                        </DrawerTitle>
                        <DrawerDescription>
                            {t("features.pets.edit.deleteDescription")}
                        </DrawerDescription>
                    </DrawerHeader>
                    <DrawerFooter>
                        <Button
                            variant="destructive"
                            size="xl"
                            onClick={onDelete}
                            disabled={isDeleting}
                            className="w-full"
                        >
                            {t("features.pets.edit.deleteConfirm")}
                        </Button>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {t("features.pets.edit.deleteTitle", { name: petName })}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t("features.pets.edit.deleteDescription")}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{t("common.actions.cancel")}</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onDelete}
                        disabled={isDeleting}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {t("features.pets.edit.deleteConfirm")}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

function PetEditSidebarTop({
    scrolled,
    isEditRoot,
    backHref,
    petName,
    pet,
    avatarUrl,
    completion,
}: {
    scrolled: boolean;
    isEditRoot: boolean;
    backHref: string;
    petName: string;
    pet: PetModel | null;
    avatarUrl: string | undefined;
    completion: number;
}) {
    const t = useTranslations();
    return (
        <div className="flex flex-col">
            <div
                className={cn(
                    "bg-card flex items-center gap-3 px-4 py-2 w-full fixed top-0 z-10 md:static md:p-0 md:pb-4",
                    scrolled && "border-b",
                )}
            >
                <Button variant="flat" size="sm" className="px-2 md:px-3" asChild>
                    <Link href={backHref}>
                        <ArrowLeft className="size-4" />
                        <span className="hidden md:block">{t("common.actions.back")}</span>
                    </Link>
                </Button>
                <h1
                    className={cn(
                        "font-semibold text-2xl md:text-3xl",
                        !isEditRoot && "hidden md:block",
                    )}
                >
                    {petName}
                </h1>
            </div>
            <div className="pt-13 md:pt-0 px-4 md:px-0">
                <div className={cn(!isEditRoot && "hidden md:flex")}>
                    <PetAvatarCard pet={pet} avatarUrl={avatarUrl} completion={completion} />
                </div>
            </div>
        </div>
    );
}

function PetEditNavCard({
    nav,
    lastSegment,
    isEditRoot,
    petName,
    isDeleting,
    onDelete,
}: {
    nav: { href: string; label: string; icon: React.ComponentType<{ className?: string }> }[];
    lastSegment: string;
    isEditRoot: boolean;
    petName: string;
    isDeleting: boolean;
    onDelete: () => void;
}) {
    return (
        <Card className="p-0 ring-0">
            <CardContent className="p-0 flex flex-col md:gap-1">
                {nav.map((item, index) => (
                    <NavRow
                        key={item.href}
                        icon={item.icon}
                        label={item.label}
                        href={item.href}
                        displayArrow={true}
                        className={cn(
                            "md:hover:bg-muted md:rounded-md md:p-4",
                            (item.href.endsWith(`/${lastSegment}`) ||
                                (isEditRoot && index === 0)) &&
                                "md:bg-muted",
                        )}
                    />
                ))}
                <DeletePetDialog petName={petName} isDeleting={isDeleting} onDelete={onDelete} />
            </CardContent>
        </Card>
    );
}

export default function PetEditLayout({
    children,
}: {
    children: React.ReactNode;
}): React.ReactElement {
    const params = useParams<{ id: string }>();
    const id = params.id;
    const locale = useLocale();
    const pathname = usePathname();
    const t = useTranslations();
    const scrolled = useScrolled(100);
    const { routes } = useNavigation();
    const router = useRouter();
    useHideBottomNavbar();

    const { pet } = usePet(id);
    const { execute: executeDelete, isLoading: isDeleting } = useAsyncState();

    const base = `/${locale}/pets/${id}/edit`;
    const avatarUrl = pet?.getAvatarUrl();
    const completion = pet ? computeCompletion(pet) : 0;

    const lastSegment = pathname.split("/").at(-1) ?? "";
    const isEditRoot = !EDIT_SECTIONS.includes(lastSegment as (typeof EDIT_SECTIONS)[number]);

    const nav = [
        {
            href: `${base}/general`,
            label: t("features.pets.edit.sections.general"),
            icon: InfoSquare,
        },
        {
            href: `${base}/health`,
            label: t("features.pets.edit.sections.health"),
            icon: DocumentMedicine,
        },
        {
            href: `${base}/personality`,
            label: t("features.pets.edit.sections.personality"),
            icon: Star,
        },
        { href: `${base}/photos`, label: t("features.pets.edit.sections.photos"), icon: Gallery },
    ];

    const currentPageLabel = nav.find((item) => item.href.endsWith(`/${lastSegment}`))?.label;
    const backHref = isEditRoot ? routes.PetDetails({ id }) : base;

    const handleDelete = () => {
        executeDelete(() => deletePet(id), {
            onSuccess: () => {
                toast.success(t("features.pets.edit.deleteSuccess"));
                router.push(routes.MyPets());
            },
        });
    };

    return (
        <div className="flex flex-col md:flex-row w-full justify-between h-fit md:h-[calc(100dvh-var(--header-height))] md:overflow-hidden">
            <div className="w-full md:w-1/3 md:p-8 md:overflow-y-auto">
                <PetEditSidebarTop
                    scrolled={scrolled}
                    isEditRoot={isEditRoot}
                    backHref={backHref}
                    petName={pet?.name ?? ""}
                    pet={pet ?? null}
                    avatarUrl={avatarUrl}
                    completion={completion}
                />

                <div className={cn("flex flex-col gap-2", !isEditRoot && "hidden md:flex")}>
                    <div className="flex flex-col gap-2 p-4 py-2 md:py-4 md:px-0 w-full">
                        <PetEditNavCard
                            nav={nav}
                            lastSegment={lastSegment}
                            isEditRoot={isEditRoot}
                            petName={pet?.name ?? ""}
                            isDeleting={isDeleting}
                            onDelete={handleDelete}
                        />
                    </div>
                </div>
            </div>

            <Separator orientation="vertical" className="hidden md:block w-[1px] h-full" />

            <div className="md:w-2/3 md:p-8 md:overflow-y-auto h-full">
                <div className="flex-1">
                    <div
                        className={cn(
                            "flex flex-col gap-3 pb-8",
                            isEditRoot && "hidden md:block",
                            !isEditRoot && "px-4 md:p-0",
                        )}
                    >
                        {!isEditRoot && (
                            <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                                {currentPageLabel}
                            </h1>
                        )}
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
