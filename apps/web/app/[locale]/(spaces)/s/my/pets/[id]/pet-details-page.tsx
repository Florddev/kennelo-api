"use client";

import { ArrowLeft, Pencil } from "lucide-react";
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
            <div className="flex items-center justify-between py-4">
                <Skeleton className="h-6 w-32 rounded-xl" />
                <div className="flex gap-2">
                    <Skeleton className="h-9 w-24 rounded-4xl" />
                    <Skeleton className="h-9 w-28 rounded-4xl" />
                </div>
            </div>
            <div className="py-6">
                <div className="flex flex-col lg:flex-row gap-8">
                    <div className="flex-1 space-y-6">
                        <Skeleton className="h-9 w-48 rounded-xl" />
                        <Skeleton className="aspect-[16/10] rounded-2xl" />
                        <Skeleton className="h-8 w-full rounded-2xl" />
                        <Skeleton className="h-36 rounded-2xl" />
                        <Skeleton className="h-72 rounded-2xl" />
                    </div>
                    <div className="lg:w-80 shrink-0 space-y-4">
                        <Skeleton className="h-72 rounded-2xl" />
                        <Skeleton className="h-36 rounded-2xl" />
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function PetDetailsPage() {
    const t = useTranslations();
    const { params, back } = useNavigation<Query>();
    const { pet, isLoading } = usePet(params.id);
    const { user } = useAuth();

    if (isLoading) {
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
        <div className="min-h-screen bg-card">
            <div className="flex justify-between items-center py-4">
                <button
                    onClick={back}
                    className="flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                >
                    <ArrowLeft className="size-4" />
                    {t("features.pets.title")}
                </button>
                {isOwner && (
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" className="gap-2 rounded-4xl">
                            <Pencil className="size-3.5" />
                            {t("features.pets.profile.edit")}
                        </Button>
                    </div>
                )}
            </div>

            <div className="pb-6">
                <div className="flex flex-col lg:flex-row gap-8">
                    <div className="flex-1 min-w-0 space-y-8">
                        <PetProfileInfo pet={pet} ageDisplay={ageDisplay} />
                        {/* <PetProfileReviews /> */}
                    </div>

                    {/* <div className="lg:w-80 shrink-0">
                        <div className="lg:sticky lg:top-22">
                            <PetProfileOwner pet={pet} currentUser={user} ageDisplay={ageDisplay} />
                        </div>
                    </div> */}
                </div>
            </div>
        </div>
    );
}
