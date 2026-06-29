"use client";

import { useTranslations } from "next-intl";
import { Skeleton } from "@workspace/ui/components/skeleton";
import type { PetModel } from "@workspace/modules/pets";
import { useNavigation } from "@/hooks/use-navigation";
import { getAge } from "@/features/pets/lib/pet-age";
import { CircleSlash, Mars, Venus } from "lucide-react";
import { ShapeMedia, ShapeMediaSkeleton } from "@/components/media/shape-media";

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
                badgeCircleClassName={`${sexUi.badgeCircleClassName} lg:size-8 xl:size-9`}
                badgeIconClassName={`${sexUi.badgeIconClassName} lg:size-5`}
                shapeClassName="lg:size-28 xl:size-38"
            />
            <div className="flex flex-col gap-2 w-full">
                <div className="flex justify-between items-center w-full">
                    <h1 className="text-2xl font-semibold">{pet.name}</h1>
                </div>
                <div className="flex flex-col">
                    <span className="text-sm text-muted-foreground">{pet.getBreedLabel()}</span>
                    <span className="text-sm text-muted-foreground">
                        {`${sexLabel}${ageLabel}, ${weightLabel}`}
                    </span>
                </div>
            </div>
        </div>
    );
}

export function PetCardSkeleton() {
    return (
        <div data-slot="pet-card-skeleton" className="flex gap-4 items-center">
            <ShapeMediaSkeleton shapeClassName="lg:size-28 xl:size-38" showBadge />
            <div className="flex flex-col gap-2 w-full">
                <Skeleton className="h-7 w-1/3" />
                <div className="flex flex-col gap-1">
                    <Skeleton className="h-4 w-1/2" />
                    <Skeleton className="h-4 w-2/3" />
                </div>
            </div>
        </div>
    );
}
