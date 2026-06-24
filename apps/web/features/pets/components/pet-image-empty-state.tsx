"use client";

import Image from "next/image";
import { PawPrint } from "lucide-react";

import { isIllustratedType } from "../lib/pet-illustrations";

export function PetImageEmptyState({ typeCode, typeName }: { typeCode: string; typeName: string }) {
    if (isIllustratedType(typeCode)) {
        return (
            <div className="relative aspect-[16/6] overflow-hidden rounded-2xl bg-muted">
                <Image
                    src={`/illustrations/pets/${typeCode}.svg`}
                    alt={typeName}
                    fill
                    className="object-contain p-12"
                />
            </div>
        );
    }

    return (
        <div className="relative aspect-[16/6] overflow-hidden bg-muted">
            <div className="absolute inset-0 flex items-center justify-center">
                <PawPrint className="size-20 text-muted-foreground/15" />
            </div>
        </div>
    );
}
