"use client";

import React from "react";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
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
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { Progress } from "@workspace/ui/components/progress";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { cn } from "@workspace/ui/lib/utils";
import { deletePet, PetModel } from "@workspace/modules/pets";

import { useAsyncState } from "@/hooks/use-async-state";
import { useIsMobile } from "@/hooks/use-mobile";
import { useNavigation } from "@/hooks/use-navigation";
import { useRouteParams } from "@/hooks/use-route-params";
import { NavRow } from "@/components/navigation/nav-row";
import { SplitPageLayout, SplitPageLayoutNavItem } from "@/components/layouts/split-page-layout";
import { usePet } from "@/features/pets/hooks/use-pet";

import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { PetEditGeneralPage } from "./general/general-edit-page";

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

function PetAvatarImage({
    pet,
    avatarUrl,
}: {
    pet: PetModel | null;
    avatarUrl: string | undefined;
}) {
    if (avatarUrl) {
        return <Image src={avatarUrl} alt={pet?.name ?? ""} fill className="object-cover" />;
    }
    if (pet) {
        return (
            <div className="size-full flex items-center justify-center">
                <GalleryMinimalistic className="size-10 text-muted-foreground mb-12" />
            </div>
        );
    }
    return <Skeleton className="size-full rounded-none" />;
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
                <PetAvatarImage pet={pet} avatarUrl={avatarUrl} />

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
            className="md:rounded-md md:p-4 md:hover:bg-destructive/10 w-full cursor-pointer"
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

function computeBackHref(
    isMobile: boolean,
    isEditRoot: boolean,
    petDetailsHref: string,
    petEditPageHref: string,
): string {
    if (isMobile && !isEditRoot) return petEditPageHref;
    return petDetailsHref;
}

export function PetEditLayout({ children }: { children: React.ReactNode }) {
    const { id } = useRouteParams<{ id: string }>();
    const pathname = usePathname();
    const t = useTranslations();
    const { routes } = useNavigation();
    const router = useRouter();
    const isMobile = useIsMobile();

    const { pet } = usePet(id);
    const { execute: executeDelete, isLoading: isDeleting } = useAsyncState();

    const avatarUrl = pet?.getAvatarUrl();
    const completion = pet ? computeCompletion(pet) : 0;

    const lastSegment = pathname.split("/").at(-1) ?? "";
    const isEditRoot = !EDIT_SECTIONS.includes(lastSegment as (typeof EDIT_SECTIONS)[number]);

    const nav: SplitPageLayoutNavItem[] = [
        {
            href: routes.PetEditGeneral({ id }),
            label: t("features.pets.edit.sections.general"),
            icon: InfoSquare,
            default: true,
        },
        {
            href: routes.PetEditHealth({ id }),
            label: t("features.pets.edit.sections.health"),
            icon: DocumentMedicine,
        },
        {
            href: routes.PetEditPersonality({ id }),
            label: t("features.pets.edit.sections.personality"),
            icon: Star,
        },
        {
            href: routes.PetEditPhotos({ id }),
            label: t("features.pets.edit.sections.photos"),
            icon: Gallery,
        },
    ];

    const currentPageLabel = nav.find((item) =>
        item.href?.split("?")[0]?.endsWith(`/${lastSegment}`),
    )?.label;

    const backHref = computeBackHref(
        isMobile,
        isEditRoot,
        routes.PetDetails({ id }),
        routes.PetEditPage({ id }),
    );

    const handleDelete = () => {
        executeDelete(() => deletePet(id), {
            onSuccess: () => {
                toast.success(t("features.pets.edit.deleteSuccess"));
                router.push(routes.MyPets());
            },
        });
    };

    return (
        <SplitPageLayout isRoot={isEditRoot}>
            <SplitPageLayout.Sidebar>
                <SplitPageLayout.Header>
                    <Button variant="flat" size="sm" className="px-2 md:px-3" asChild>
                        <Link href={backHref}>
                            <ArrowLeft className="size-4" />
                            <span className="hidden md:inline">{t("common.actions.back")}</span>
                        </Link>
                    </Button>
                    {pet?.name ? (
                        <h1
                            className={cn(
                                "font-semibold text-2xl md:text-3xl",
                                !isEditRoot && "hidden md:block",
                            )}
                        >
                            {pet.name}
                        </h1>
                    ) : (
                        <Skeleton
                            className={cn("h-8 w-40 rounded-xl", !isEditRoot && "hidden md:block")}
                        />
                    )}
                </SplitPageLayout.Header>

                <div className={cn(!isEditRoot && "hidden md:flex")}>
                    <PetAvatarCard
                        pet={pet ?? null}
                        avatarUrl={avatarUrl}
                        completion={completion}
                    />
                </div>

                <SplitPageLayout.Nav>
                    <Card className="p-0 ring-0">
                        <CardContent className="p-0 flex flex-col md:gap-1">
                            {nav.map((item) => (
                                <NavRow
                                    key={item.href}
                                    icon={item.icon}
                                    label={item.label}
                                    href={item.href}
                                    displayArrow={true}
                                    className={cn(
                                        "md:hover:bg-muted md:rounded-md md:p-4",
                                        ((item.href?.split("?")[0]?.endsWith(`/${lastSegment}`) ??
                                            false) ||
                                            (isEditRoot && item.default)) &&
                                            "md:bg-muted",
                                    )}
                                />
                            ))}
                            <DeletePetDialog
                                petName={pet?.name ?? ""}
                                isDeleting={isDeleting}
                                onDelete={handleDelete}
                            />
                        </CardContent>
                    </Card>
                </SplitPageLayout.Nav>
            </SplitPageLayout.Sidebar>

            <SplitPageLayout.Content
                sectionTitle={currentPageLabel}
                defaultContent={<PetEditGeneralPage />}
            >
                {children}
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}
