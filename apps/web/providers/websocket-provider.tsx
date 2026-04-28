"use client";

import { useEffect } from "react";
import { echoClient } from "@workspace/common";
import { authService } from "@workspace/modules/users";
import { useAuth } from "@/features/auth";

export function WebsocketProvider() {
    const { user } = useAuth();

    useEffect(() => {
        if (!user) {
            echoClient.disconnect();
            return;
        }
        echoClient.setTokenGetter(() => authService.getAccessToken());
        echoClient.connect();
    }, [user]);

    return null;
}
