"use client";

import { useEffect } from "react";

import { useNavVisibility } from "@/providers/navigation-visibility-provider";

export function useHideBottomNavbar() {
    const { setBottomNavbarVisible } = useNavVisibility();

    useEffect(() => {
        setBottomNavbarVisible(false);
        return () => setBottomNavbarVisible(true);
    }, [setBottomNavbarVisible]);
}
