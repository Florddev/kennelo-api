"use client";

import { PawPrint } from "lucide-react";
import { Badge } from "@workspace/ui/components/badge";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { useActivityCycleSettings } from "../hooks/use-activity-cycle-settings";

type ActivityAnimalTypesDisplayProps = {
    activityId: string;
    emptyLabel: string;
};

export function ActivityAnimalTypesDisplay({
    activityId,
    emptyLabel,
}: ActivityAnimalTypesDisplayProps) {
    const { settings, isLoading } = useActivityCycleSettings(activityId);

    if (isLoading) {
        return (
            <div className="flex flex-wrap gap-2">
                {Array.from({ length: 3 }).map((_, index) => (
                    <Skeleton key={index} className="h-8 w-24 rounded-4xl" />
                ))}
            </div>
        );
    }

    if (settings.length === 0) {
        return <p className="text-sm text-muted-foreground italic">{emptyLabel}</p>;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {settings.map((setting) => {
                const code = setting.animalType.code.toLowerCase();

                return (
                    <Badge
                        key={setting.id}
                        variant="outline"
                        className="gap-1.5 rounded-4xl py-1.5 ps-1.5 pe-3"
                    >
                        <span className="flex size-5 items-center justify-center">
                            {isIllustratedType(code) ? (
                                <PetTypeIllustration
                                    code={code}
                                    name={setting.animalType.name}
                                    className="size-5"
                                />
                            ) : (
                                <PawPrint className="size-4 text-muted-foreground" />
                            )}
                        </span>
                        {setting.animalType.name}
                    </Badge>
                );
            })}
        </div>
    );
}
