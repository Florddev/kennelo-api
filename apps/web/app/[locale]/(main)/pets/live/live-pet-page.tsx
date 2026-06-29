"use client";

import { useTranslations } from "next-intl";
import { PawPrint, CircleSlash, Mars, Venus } from "lucide-react";

import { Badge } from "@workspace/ui/components/badge";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { cn } from "@workspace/ui/lib/utils";

import { usePetBroadcast } from "@/features/pets";
import { getAge } from "@/features/pets/lib/pet-age";
import { PetTypeIllustration } from "@/features/pets";
import { ShapeMedia } from "@/components/media/shape-media";

const sexIconMap = {
    female: { icon: Venus, circleClassName: "text-pink-100", iconClassName: "text-pink-400" },
    male: { icon: Mars, circleClassName: "text-sky-100", iconClassName: "text-sky-400" },
    unknown: {
        icon: CircleSlash,
        circleClassName: "text-muted",
        iconClassName: "text-muted-foreground/50",
    },
} as const;

function PetBroadcastSkeleton() {
    return (
        <div className="rounded-2xl border bg-card p-6 flex flex-col gap-6">
            <div className="flex items-center gap-5">
                <Skeleton className="size-24 rounded-full shrink-0" />
                <div className="flex flex-col gap-2 flex-1">
                    <Skeleton className="h-7 w-40" />
                    <Skeleton className="h-4 w-28" />
                    <Skeleton className="h-4 w-20 mt-1" />
                </div>
            </div>
            <div className="flex gap-2">
                <Skeleton className="h-6 w-16 rounded-full" />
                <Skeleton className="h-6 w-20 rounded-full" />
                <Skeleton className="h-6 w-14 rounded-full" />
            </div>
            <div className="flex flex-col gap-1.5">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-3/4" />
            </div>
        </div>
    );
}

type BroadcastedPet = NonNullable<ReturnType<typeof usePetBroadcast>["broadcastedPet"]>;

function PetBroadcastCard({ pet }: { pet: BroadcastedPet }) {
    const t = useTranslations();

    const normalizedSex = pet.sex === "female" || pet.sex === "male" ? pet.sex : "unknown";
    const sexUi = sexIconMap[normalizedSex];

    const ageDisplay = (() => {
        if (!pet.birthDate) return null;
        const { years, months } = getAge(pet.birthDate);
        if (years >= 1) return t("features.pets.age.years", { count: years });
        return t("features.pets.age.months", { count: months });
    })();

    return (
        <div className="rounded-2xl border bg-card p-6 flex flex-col gap-6">
            <div className="flex items-center gap-5">
                <ShapeMedia
                    imageUrl={pet.getAvatarUrl()}
                    badgeIcon={sexUi.icon}
                    badgeCircleClassName={cn(sexUi.circleClassName, "size-8")}
                    badgeIconClassName={cn(sexUi.iconClassName, "size-5")}
                    shapeClassName="size-24"
                />
                <div className="flex flex-col gap-1 min-w-0">
                    <h1 className="text-2xl font-bold truncate">{pet.name}</h1>
                    {pet.getBreedLabel() && (
                        <span className="text-sm text-muted-foreground truncate">
                            {pet.getBreedLabel()}
                        </span>
                    )}
                    {pet.animalType && (
                        <div className="flex items-center gap-1.5 mt-1">
                            <PetTypeIllustration
                                code={pet.animalType.code}
                                name={pet.animalType.name}
                                className="size-5"
                            />
                            <span className="text-sm text-muted-foreground capitalize">
                                {pet.animalType.name}
                            </span>
                        </div>
                    )}
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                {pet.sex && pet.sex !== "unknown" && (
                    <Badge variant="outline" className="capitalize">
                        {t(`features.pets.sex.${pet.sex}`)}
                    </Badge>
                )}
                {ageDisplay && <Badge variant="outline">{ageDisplay}</Badge>}
                {pet.weight && <Badge variant="outline">{pet.weight} kg</Badge>}
                {pet.isSterilized && (
                    <Badge variant="outline">{t("features.pets.badges.sterilized")}</Badge>
                )}
                {pet.hasMicrochip && (
                    <Badge variant="outline">{t("features.pets.badges.microchipped")}</Badge>
                )}
            </div>

            {pet.about && (
                <p className="text-sm text-muted-foreground leading-relaxed">{pet.about}</p>
            )}
        </div>
    );
}

export default function LivePetPage() {
    const t = useTranslations();
    const { broadcastedPet, hasBroadcast } = usePetBroadcast();

    if (!hasBroadcast) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[60vh] gap-6 text-center px-4">
                <div className="relative flex items-center justify-center">
                    <span className="absolute size-32 rounded-full bg-primary/10 animate-ping opacity-40" />
                    <span className="absolute size-24 rounded-full bg-primary/15" />
                    <PawPrint className="relative size-12 text-primary" />
                </div>
                <div className="flex flex-col gap-2">
                    <p className="text-xl font-semibold">{t("features.pets.live.waiting")}</p>
                    <p className="text-sm text-muted-foreground max-w-xs">
                        {t("features.pets.live.waitingDescription")}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-6 py-8 max-w-lg mx-auto">
            <div className="flex items-center gap-2">
                <span className="size-2.5 rounded-full bg-green-500 animate-pulse" />
                <span className="text-sm font-medium text-green-600 dark:text-green-400">
                    {t("features.pets.live.received")}
                </span>
            </div>

            {broadcastedPet ? <PetBroadcastCard pet={broadcastedPet} /> : <PetBroadcastSkeleton />}
        </div>
    );
}
