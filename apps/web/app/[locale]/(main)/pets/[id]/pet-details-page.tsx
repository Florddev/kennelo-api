"use client";

import Image from "next/image";
import { ArrowLeft, PenNewSquare, Share } from "@solar-icons/react";
import { PawPrint } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { usePet } from "@/features/pets/hooks/use-pet";
import { PetProfileInfo } from "@/features/pets/components/pet-profile-info";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { getAge } from "@/features/pets/lib/pet-age";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { DetailPageLayout } from "@/components/layouts/detail-page-layout";
import Link from "next/link";
import { useIsMobile } from "@/hooks/use-mobile";

type Query = { id: string };

function PetDetailsPageSkeleton() {
    return (
        <div className="min-h-screen">
            <div className="absolute top-0 w-full z-10 flex justify-between items-center p-2">
                <Skeleton className="size-8 rounded-4xl" />
                <div className="flex gap-0.5">
                    <Skeleton className="size-8 rounded-4xl" />
                    <Skeleton className="size-8 rounded-4xl" />
                </div>
            </div>

            <div className="pb-20 sm:pb-6">
                <div className="flex flex-col lg:flex-row gap-8">
                    <div className="flex-1 min-w-0 space-y-8">
                        <div className="space-y-6">
                            <div className="flex flex-col">
                                <Skeleton className="h-72 w-full rounded-none sm:rounded-3xl" />

                                <div className="-mt-6 z-10 bg-card rounded-3xl p-4 sm:mt-0 sm:px-0">
                                    <div className="flex flex-col gap-6">
                                        <div className="flex gap-2 items-center">
                                            <div className="flex-1 space-y-2.5">
                                                <Skeleton className="h-9 w-44 rounded-xl" />
                                                <div className="flex items-center gap-2">
                                                    <Skeleton className="h-4 w-20 rounded-xl" />
                                                    <Skeleton className="h-4 w-24 rounded-xl" />
                                                </div>
                                            </div>
                                            <Skeleton className="size-12 rounded-full" />
                                        </div>

                                        <div className="space-y-3">
                                            <Skeleton className="h-6 w-28 rounded-xl" />
                                            <Skeleton className="h-4 w-full rounded-xl" />
                                            <Skeleton className="h-4 w-5/6 rounded-xl" />
                                            <div className="flex flex-wrap gap-2">
                                                <Skeleton className="h-7 w-20 rounded-4xl" />
                                                <Skeleton className="h-7 w-24 rounded-4xl" />
                                                <Skeleton className="h-7 w-18 rounded-4xl" />
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="grid grid-cols-3 gap-2 border-b pb-2">
                                                <Skeleton className="h-8 w-full rounded-xl" />
                                                <Skeleton className="h-8 w-full rounded-xl" />
                                                <Skeleton className="h-8 w-full rounded-xl" />
                                            </div>

                                            <div className="space-y-6">
                                                <div className="rounded-3xl space-y-4">
                                                    <Skeleton className="h-6 w-40 rounded-xl" />
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                    </div>
                                                </div>

                                                <div className="space-y-3">
                                                    <Skeleton className="h-6 w-32 rounded-xl" />
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                    </div>
                                                    <Skeleton className="h-24 rounded-2xl" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="fixed bottom-0 w-full z-20 h-14 bg-card border-t px-2 flex justify-center items-center sm:hidden">
                <Skeleton className="h-10 w-full rounded-4xl" />
            </div>
        </div>
    );
}

export default function PetDetailsPage() {
    const t = useTranslations();
    const { params, routes } = useNavigation<Query>();
    const { pet, isLoading } = usePet(params.id);
    const isMobile = useIsMobile();
    const { user } = useAuth();

    if (!pet && isLoading) {
        return <PetDetailsPageSkeleton />;
    }

    if (!pet) {
        return null;
    }

    const ageDisplay = pet.birthDate
        ? (() => {
              const { years, months } = getAge(pet.birthDate);
              if (years >= 1) return t("features.pets.age.years", { count: years });
              return t("features.pets.age.months", { count: months });
          })()
        : null;

    const isOwner = user?.id === pet.userId;
    const typeCode = pet.animalType?.code?.toLowerCase() ?? "";
    const images = [...(pet.avatarUrl ? [pet.avatarUrl] : []), ...pet.images.map((img) => img.url)];

    const emptyState = isIllustratedType(typeCode) ? (
        <div className="relative aspect-[16/6] rounded-2xl overflow-hidden bg-muted">
            <Image
                src={`/illustrations/pets/${typeCode}.svg`}
                alt={pet.animalType?.name ?? ""}
                fill
                className="object-contain p-12"
            />
        </div>
    ) : (
        <div className="relative aspect-[16/6] overflow-hidden bg-muted">
            <div className="absolute inset-0 flex items-center justify-center">
                <PawPrint className="size-20 text-muted-foreground/15" />
            </div>
        </div>
    );

    return (
        <DetailPageLayout
            images={images}
            altPrefix={pet.name}
            emptyState={emptyState}
            desktopCtaLabel={t("features.pets.profile.viewPhotos", { count: images.length })}
            headerStart={
                <Button size="icon-sm" className="text-primary bg-card hover:bg-muted" asChild>
                    <Link href={routes.MyPets()}>
                        <ArrowLeft />
                    </Link>
                </Button>
            }
            headerEnd={
                <>
                    {isOwner && (
                        <Button
                            size="sm"
                            className="text-primary bg-card hover:bg-muted gap-1.5"
                            asChild
                        >
                            <Link
                                href={
                                    isMobile
                                        ? routes.PetEditPage({ id: pet.id })
                                        : routes.PetEditGeneral({ id: pet.id })
                                }
                            >
                                <PenNewSquare />
                                {t("common.actions.edit")}
                            </Link>
                        </Button>
                    )}
                </>
            }
            footer={
                isOwner ? (
                    <div className="h-16 bg-card border-t px-2 flex justify-center items-center sm:hidden">
                        <Button className="w-full" size="xl">
                            {t("features.pets.profile.findHost", { name: pet.name })}
                        </Button>
                    </div>
                ) : undefined
            }
            className="pb-6"
        >
            <PetProfileInfo pet={pet} ageDisplay={ageDisplay} />
        </DetailPageLayout>
    );
}
