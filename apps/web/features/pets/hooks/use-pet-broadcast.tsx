"use client";

import { useEffect, useState } from "react";
import { useQuery } from "@tanstack/react-query";

import { echoClient } from "@workspace/common";
import { getPetByMicrochip } from "@workspace/modules/pets";

import { useAuth } from "@/features/auth";

export function usePetBroadcast() {
    const { user } = useAuth();
    const [microchipNumber, setMicrochipNumber] = useState<string | null>(null);

    useEffect(() => {
        if (!user) return;

        const channel = echoClient.private(`user.${user.id}`);

        channel.listen(
            ".pet.broadcast",
            (event: { microchip_number: string; scanner_code: string }) => {
                setMicrochipNumber(event.microchip_number);
            },
        );

        return () => {
            echoClient.leave(`user.${user.id}`);
        };
    }, [user]);

    const { data: broadcastedPet = null, isLoading } = useQuery({
        queryKey: ["pet-broadcast", microchipNumber],
        queryFn: () => getPetByMicrochip(microchipNumber!),
        enabled: !!microchipNumber,
    });

    return {
        broadcastedPet,
        isLoading: !!microchipNumber && isLoading,
        hasBroadcast: !!microchipNumber,
    };
}
