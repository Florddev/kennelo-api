"use client";

import { useTranslations } from "next-intl";
import { Skeleton } from "@workspace/ui/components/skeleton";
import type { PetModel } from "@workspace/modules/pets";
import { useNavigation } from "@/hooks/use-navigation";
import { getAge } from "@/features/pets/lib/pet-age";
import { Badge } from "@workspace/ui/components/badge";
import { CircleSlash, Mars, Star, Venus } from "lucide-react";
import { ShapeMedia } from "@/components/media/shape-media";

type PetCardProps = {
    pet: PetModel;
};

const sexUiByKey = {
    female: {
        icon: Venus,
        badgeCircleClassName: "text-pink-100",
        badgeIconClassName: "text-pink-400",
    },
    male: {
        icon: Mars,
        badgeCircleClassName: "text-sky-100",
        badgeIconClassName: "text-sky-400",
    },
    unknown: {
        icon: CircleSlash,
        badgeCircleClassName: "text-muted",
        badgeIconClassName: "text-muted-foreground/50",
    },
} as const;

export function PetCard({ pet }: PetCardProps) {
    const t = useTranslations();
    const { routes, push } = useNavigation();
    const normalizedSex = pet.sex === "female" || pet.sex === "male" ? pet.sex : "unknown";
    const sexUi = sexUiByKey[normalizedSex];

    const ageDisplay = pet.birthDate
        ? (() => {
              const { years, months } = getAge(pet.birthDate);
              if (years >= 1) return t("features.pets.age.years", { count: years });
              return t("features.pets.age.months", { count: months });
          })()
        : null;

    const sexLabel = pet.sex !== "unknown" ? `${pet.sex}, ` : "";
    const ageLabel = ageDisplay ?? "—";
    const weightLabel = pet.weight ? `${pet.weight} kg` : "—";

    return (
        <div
            data-slot="pet-card"
            className="flex gap-4 items-center cursor-pointer"
            onClick={() => push(routes.PetDetails({ id: pet.id }))}
        >
            <ShapeMedia
                imageUrl={pet.getAvatarUrl()}
                badgeIcon={sexUi.icon}
                badgeCircleClassName={`${sexUi.badgeCircleClassName} lg:size-8 xl:size-10`}
                badgeIconClassName={`${sexUi.badgeIconClassName} lg:size-5`}
                shapeClassName="lg:size-38 xl:size-56"
            />
            <div className="flex flex-col gap-2 w-full">
                <div className="flex justify-between items-center w-full">
                    <h1 className="text-2xl font-semibold">{pet.name}</h1>
                </div>
                <div className="flex flex-col">
                    <span className="text-sm text-muted-foreground">{pet.breed}</span>
                    <span className="text-sm text-muted-foreground">
                        {`${sexLabel}${ageLabel}, ${weightLabel}`}
                    </span>
                </div>
                <Badge variant="outline" className="text-amber-500 bg-amber-100 border-0">
                    <Star className="size-4" />
                    4.4
                </Badge>
            </div>
        </div>
    );
}

export function PetCardSkeleton() {
    return (
        <div className="rounded-2xl overflow-hidden border bg-card">
            <Skeleton className="aspect-[4/3] w-full rounded-none" />
            <div className="p-3.5 space-y-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="space-y-1.5 flex-1">
                        <Skeleton className="h-4 w-2/3" />
                        <Skeleton className="h-3 w-1/2" />
                    </div>
                    <Skeleton className="size-6 rounded-lg shrink-0" />
                </div>
                <div className="grid grid-cols-3 gap-2">
                    <Skeleton className="h-16 rounded-2xl" />
                    <Skeleton className="h-16 rounded-2xl" />
                    <Skeleton className="h-16 rounded-2xl" />
                </div>
                <Skeleton className="h-10 rounded-2xl" />
            </div>
        </div>
    );
}
