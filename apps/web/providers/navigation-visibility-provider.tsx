"use client";

import { createContext, useContext, useState } from "react";
import type { ReactNode } from "react";

type NavigationVisibilityContextValue = {
    isBottomNavbarVisible: boolean;
    setBottomNavbarVisible: (visible: boolean) => void;
};

const NavigationVisibilityContext = createContext<NavigationVisibilityContextValue>({
    isBottomNavbarVisible: true,
    setBottomNavbarVisible: () => {},
});

export function NavigationVisibilityProvider({ children }: { children: ReactNode }) {
    const [isBottomNavbarVisible, setBottomNavbarVisible] = useState(true);

    return (
        <NavigationVisibilityContext.Provider
            value={{ isBottomNavbarVisible, setBottomNavbarVisible }}
        >
            {children}
        </NavigationVisibilityContext.Provider>
    );
}

export function useNavVisibility() {
    return useContext(NavigationVisibilityContext);
}
