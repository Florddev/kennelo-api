"use client";

import { ArrowLeft, Heart, Share2 } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { usePet } from "@/features/pets/hooks/use-pet";
import { PetProfileInfo } from "@/features/pets/components/pet-profile-info";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { getAge } from "@/features/pets/lib/pet-age";

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
    const { params, back } = useNavigation<Query>();
    const { pet, isLoading } = usePet(params.id);
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

    return (
        <div className="min-h-screen">
            <div className="absolute top-0 w-full z-10 sm:static flex justify-between items-center p-2">
                <Button size="icon-sm" className="text-primary bg-card" onClick={back}>
                    <ArrowLeft />
                </Button>
                <div className="flex gap-0.5">
                    <Button size="icon-sm" className="text-primary bg-card">
                        <Heart />
                    </Button>
                    <Button size="icon-sm" className="text-primary bg-card">
                        <Share2 />
                    </Button>
                </div>
            </div>

            {isOwner && (
                <div className="fixed bottom-0 w-full z-20 h-14 bg-card border-t px-2 flex justify-center items-center sm:hidden">
                    <Button className="w-full" size="lg">
                        {t("features.pets.profile.edit")}
                    </Button>
                </div>
            )}

            <div className="pb-6">
                <div className="flex flex-col lg:flex-row gap-8">
                    <div className="flex-1 min-w-0 space-y-8">
                        <PetProfileInfo pet={pet} ageDisplay={ageDisplay} />
                    </div>
                </div>
            </div>
        </div>
    );
}
