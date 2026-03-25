"use client";

import { useSyncExternalStore } from "react";
import { getPlatform, isNative, isAndroid, isIos, isCapacitorApp, isBrowser } from "@/lib/platform";

interface PlatformInfo {
    platform: "web" | "android" | "ios" | "server";
    isNative: boolean;
    isAndroid: boolean;
    isIos: boolean;
    isCapacitorApp: boolean;
    isBrowser: boolean;
    isReady: boolean;
}

const SERVER_SNAPSHOT: PlatformInfo = {
    platform: "server",
    isNative: false,
    isAndroid: false,
    isIos: false,
    isCapacitorApp: false,
    isBrowser: false,
    isReady: false,
};

const subscribe = () => () => {};

let clientSnapshot: PlatformInfo | null = null;

const getSnapshot = (): PlatformInfo => {
    if (clientSnapshot) {
        return clientSnapshot;
    }

    clientSnapshot = {
        platform: getPlatform() as "web" | "android" | "ios" | "server",
        isNative: isNative(),
        isAndroid: isAndroid(),
        isIos: isIos(),
        isCapacitorApp: isCapacitorApp(),
        isBrowser: isBrowser(),
        isReady: true,
    };

    return clientSnapshot;
};

const getServerSnapshot = (): PlatformInfo => SERVER_SNAPSHOT;

export function usePlatform(): PlatformInfo {
    return useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);
}
