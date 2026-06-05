"use client";

import { useMemo, useState } from "react";
import type { PetModel } from "@workspace/modules/pets";

type AvailableType = {
    id: string;
    name: string;
    code: string;
    count: number;
};

export function usePetsFilters(pets: PetModel[]) {
    const [search, setSearch] = useState("");
    const [typeFilter, setTypeFilter] = useState<string | null>(null);

    const availableTypes = useMemo<AvailableType[]>(() => {
        const map = new Map<string, AvailableType>();
        pets.forEach((p) => {
            if (p.animalType) {
                const entry = map.get(p.animalTypeId);
                if (entry) {
                    entry.count++;
                } else {
                    map.set(p.animalTypeId, {
                        id: p.animalTypeId,
                        name: p.animalType.name,
                        code: p.animalType.code,
                        count: 1,
                    });
                }
            }
        });
        return Array.from(map.values()).sort((a, b) => b.count - a.count);
    }, [pets]);

    const filteredPets = useMemo(() => {
        let result = pets;

        if (search.trim()) {
            const q = search.toLowerCase();
            result = result.filter(
                (p) =>
                    p.name.toLowerCase().includes(q) || (p.breed ?? "").toLowerCase().includes(q),
            );
        }

        if (typeFilter !== null) {
            result = result.filter((p) => p.animalTypeId === typeFilter);
        }

        return [...result].sort((a, b) => b.createdAt.localeCompare(a.createdAt));
    }, [pets, search, typeFilter]);

    return {
        search,
        setSearch,
        typeFilter,
        setTypeFilter,
        filteredPets,
        availableTypes,
    };
}
