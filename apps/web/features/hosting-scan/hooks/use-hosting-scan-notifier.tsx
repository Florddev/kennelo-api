"use client";

import { useEffect, useRef } from "react";
import { usePathname } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { toast } from "sonner";

import { echoClient, isActivePath } from "@workspace/common";

import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { ScanToastCard } from "../components/scan-toast";

type PetBroadcastEvent = {
    microchip_number: string;
    found: boolean;
    pet: { name: string } | null;
};

export function useHostingScanNotifier() {
    const { user } = useAuth();
    const { push, routes } = useNavigation();
    const pathname = usePathname();
    const locale = useLocale();
    const t = useTranslations("features.hosting-scan.notification");

    const handler = (event: PetBroadcastEvent) => {
        const microchip = event.microchip_number;
        const detailPath = routes.HostingScanResult({ microchip });

        if (isActivePath(routes.HostingScan(), pathname, locale)) {
            push(detailPath);
            return;
        }

        const title = event.found ? t("foundTitle") : t("notFoundTitle");
        const subtitle = event.found && event.pet ? event.pet.name : microchip;

        toast.custom((id) => (
            <ScanToastCard
                title={title}
                subtitle={subtitle}
                actionLabel={t("view")}
                onClick={() => {
                    toast.dismiss(id);
                    push(detailPath);
                }}
            />
        ));
    };

    const handlerRef = useRef(handler);

    useEffect(() => {
        handlerRef.current = handler;
    });

    useEffect(() => {
        if (!user) return;

        const channel = echoClient.private(`user.${user.id}`);
        const listener = (event: PetBroadcastEvent) => handlerRef.current(event);

        channel.listen(".pet.broadcast", listener);

        return () => {
            channel.stopListening(".pet.broadcast", listener);
        };
    }, [user]);
}
