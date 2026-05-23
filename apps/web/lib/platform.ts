import { Capacitor } from "@capacitor/core";

export const getPlatform = () => {
    if (typeof window === "undefined") return "server";
    return Capacitor.getPlatform();
};

export const isNative = () => {
    if (typeof window === "undefined") return false;
    return Capacitor.isNativePlatform();
};

export const isCapacitorApp = () => isNative() || process.env.NEXT_PUBLIC_PLATFORM === "mobile";
export const isBrowser = () => !isNative();

export const isAndroid = () => getPlatform() === "android";
export const isIos = () => getPlatform() === "ios";
