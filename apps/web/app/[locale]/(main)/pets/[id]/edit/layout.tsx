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
    InfoSquare,
    Star,
    TrashBinTrash,
    UserCircle,
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
import { cn } from "@workspace/ui/lib/utils";
import { useNavigation } from "@/hooks/use-navigation";
import { useRouter } from "next/navigation";
import { deletePet, PetModel } from "@workspace/modules/pets";
import { useAsyncState } from "@/hooks/use-async-state";
import { usePet } from "@/features/pets/hooks/use-pet";
import { toast } from "sonner";

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
        <div className="flex flex-col items-center gap-3 py-4 md:py-6">
            <div className="relative size-20 rounded-full overflow-hidden bg-muted border-2 border-border shrink-0">
                {avatarUrl ? (
                    <Image src={avatarUrl} alt={pet?.name ?? ""} fill className="object-cover" />
                ) : (
                    <div className="size-full flex items-center justify-center">
                        <UserCircle className="size-10 text-muted-foreground" />
                    </div>
                )}
            </div>
            <h1 className="font-semibold text-xl text-center">{pet?.name ?? ""}</h1>
            <div className="w-full px-2">
                <div className="flex justify-between text-xs text-muted-foreground mb-1.5">
                    <span>{t("features.pets.edit.completionLabel")}</span>
                    <span>{completion}%</span>
                </div>
                <div className="w-full bg-muted rounded-full h-1.5">
                    <div
                        className="bg-primary h-1.5 rounded-full transition-all duration-500"
                        style={{ width: `${completion}%` }}
                    />
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
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <NavRow
                    icon={TrashBinTrash}
                    label={t("features.pets.edit.delete")}
                    destructive
                    className="md:rounded-md md:p-4"
                />
            </AlertDialogTrigger>
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
                <div className="flex flex-col">
                    <div
                        className={cn(
                            "bg-card flex items-center gap-3 px-4 py-2 w-full fixed top-0 z-10 md:hidden",
                            scrolled && "border-b",
                        )}
                    >
                        <Button variant="flat" size="icon-sm" asChild>
                            <Link href={isEditRoot ? routes.PetDetails({ id }) : base}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                    </div>

                    <div className="pt-13 md:pt-0 px-4 md:px-0">
                        <div className={cn(!isEditRoot && "hidden md:flex")}>
                            <PetAvatarCard
                                pet={pet ?? null}
                                avatarUrl={avatarUrl}
                                completion={completion}
                            />
                        </div>
                    </div>
                </div>

                <div className={cn("flex flex-col gap-2", !isEditRoot && "hidden md:flex")}>
                    <div className="flex flex-col gap-2 p-4 py-2 md:py-4 md:px-0 w-full">
                        <Card className="p-0 ring-0">
                            <CardContent className="p-0 flex flex-col md:gap-1">
                                {nav.map((item: (typeof nav)[number], index: number) => (
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
                            </CardContent>
                        </Card>

                        <Card className="p-0 ring-0">
                            <CardContent className="p-0">
                                <DeletePetDialog
                                    petName={pet?.name ?? ""}
                                    isDeleting={isDeleting}
                                    onDelete={handleDelete}
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <Separator orientation="vertical" className="hidden md:block w-[1px] h-full" />

            <div className="md:w-2/3 md:p-8 md:overflow-y-auto h-full">
                <div className="flex-1 md:py-6 md:pt-0">
                    <div
                        className={cn(
                            "flex flex-col gap-3",
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
