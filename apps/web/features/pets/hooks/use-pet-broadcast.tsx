"use client";

import { useEffect, useState } from "react";

import { echoClient } from "@workspace/common";
import { PetModel, type AnimalTypeDto } from "@workspace/modules/pets";

import { useAuth } from "@/features/auth";

type BroadcastPetDto = {
    id: string;
    animal_type_id: string;
    name: string;
    breed: string | null;
    birth_date: string | null;
    sex: "male" | "female" | "unknown" | null;
    weight: number | null;
    is_sterilized: boolean | null;
    has_microchip: boolean;
    microchip_number: string | null;
    about: string | null;
    avatar_url: string | null;
    animal_type: AnimalTypeDto | null;
    created_at: string;
    updated_at: string;
};

type PetBroadcastEvent = {
    microchip_number: string;
    scanner_code: string;
    found: boolean;
    pet: BroadcastPetDto | null;
};

export function usePetBroadcast() {
    const { user } = useAuth();
    const [broadcastedPet, setBroadcastedPet] = useState<PetModel | null>(null);
    const [hasBroadcast, setHasBroadcast] = useState(false);

    useEffect(() => {
        if (!user) return;

        const channel = echoClient.private(`user.${user.id}`);

        channel.listen(".pet.broadcast", (event: PetBroadcastEvent) => {
            setHasBroadcast(true);
            setBroadcastedPet(
                event.pet
                    ? PetModel.from({
                          ...event.pet,
                          user_id: "",
                          adoption_date: null,
                          health_notes: null,
                      })
                    : null,
            );
        });

        return () => {
            echoClient.leave(`user.${user.id}`);
        };
    }, [user]);

    return { broadcastedPet, hasBroadcast };
}
