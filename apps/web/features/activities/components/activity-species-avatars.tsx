"use client";

import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";

import {
    Avatar,
    AvatarFallback,
    AvatarGroup,
    AvatarGroupCount,
} from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";
import type { ActivityCycleSettingModel } from "@workspace/modules/activities";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

const MAX_VISIBLE_SPECIES = 4;

type ActivitySpeciesAvatarsProps = {
    settings: ActivityCycleSettingModel[];
    className?: string;
};

export function ActivitySpeciesAvatars({ settings, className }: ActivitySpeciesAvatarsProps) {
    const t = useTranslations();

    if (settings.length === 0) {
        return (
            <p className="text-xs text-muted-foreground italic">
                {t("features.activities.card.noSpecies")}
            </p>
        );
    }

    const visible = settings.slice(0, MAX_VISIBLE_SPECIES);
    const remaining = settings.length - visible.length;

    return (
        <AvatarGroup data-slot="activity-species-avatars" className={cn(className)}>
            {visible.map((setting) => (
                <Avatar key={setting.id} className="size-9 bg-card">
                    <AvatarFallback className="bg-muted">
                        {isIllustratedType(setting.animalType.code) ? (
                            <PetTypeIllustration
                                code={setting.animalType.code}
                                name={setting.animalType.name}
                                className="size-5"
                            />
                        ) : (
                            <PawPrint className="size-4 text-muted-foreground" />
                        )}
                    </AvatarFallback>
                </Avatar>
            ))}
            {remaining > 0 && (
                <AvatarGroupCount className="size-9 text-xs font-semibold">
                    +{remaining}
                </AvatarGroupCount>
            )}
        </AvatarGroup>
    );
}
