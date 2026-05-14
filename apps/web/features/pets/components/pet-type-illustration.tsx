"use client";

import Image from "next/image";
import { cn } from "@workspace/ui/lib/utils";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

type PetTypeIllustrationProps = {
    code: string;
    name?: string;
    className?: string;
};

export function PetTypeIllustration({ code, name = "", className }: PetTypeIllustrationProps) {
    const normalized = code.toLowerCase();

    if (isIllustratedType(normalized)) {
        return (
            <Image
                src={`/illustrations/pets/${normalized}.svg`}
                alt={name}
                width={20}
                height={20}
                className={cn("object-contain shrink-0", className)}
            />
        );
    }

    return null;
}
