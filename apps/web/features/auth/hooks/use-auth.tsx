"use client";

import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { UserModel } from "@workspace/modules/users";
import { getCurrentUser, logoutUser, authService } from "@workspace/modules/users";
import { getEstablishments, EstablishmentModel } from "@workspace/modules/establishments";
import { api } from "@workspace/common";
import { useRouter } from "next/navigation";
import { logger } from "@/lib/logger";
import { routes } from "@/lib/routes";
import { getAppStorage } from "@/lib/storage";

interface AuthContextValue {
    user: UserModel | null;
    establishments: EstablishmentModel[];
    isLoading: boolean;
    isLoaded: boolean;
    isAuthenticated: boolean;
    hasEstablishment: boolean;
    logout: () => Promise<void>;
    refreshUser: () => Promise<UserModel | null>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({
    children,
    initialIsAuthenticated = false,
}: {
    children: ReactNode;
    initialIsAuthenticated?: boolean;
}) {
    const [user, setUser] = useState<UserModel | null>(null);
    const [establishments, setEstablishments] = useState<EstablishmentModel[]>([]);
    const [isLoading, setIsLoading] = useState(initialIsAuthenticated);
    const [isAuthenticated, setIsAuthenticated] = useState(initialIsAuthenticated);
    const router = useRouter();

    useState(() => {
        authService.configure(getAppStorage());
        api.setTokenGetter(() => authService.getAccessToken());
    });

    const loadUser = async (): Promise<UserModel | null> => {
        if (!(await authService.isAuthenticated())) {
            setIsAuthenticated(false);
            setUser(null);
            setEstablishments([]);
            setIsLoading(false);
            return null;
        }

        setIsAuthenticated(true);

        try {
            const [currentUser, userEstablishments] = await Promise.all([
                getCurrentUser(),
                getEstablishments().catch(() => []),
            ]);
            setUser(currentUser);
            setEstablishments(userEstablishments);
            return currentUser;
        } catch (error) {
            logger.error("Failed to load user:", error);
            setIsAuthenticated(false);
            setUser(null);
            setEstablishments([]);
            await authService.clearTokens();
            return null;
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        loadUser();
    }, []);

    const logout = async () => {
        try {
            await logoutUser();
        } catch (error) {
            logger.error("Logout error:", error);
        } finally {
            setIsAuthenticated(false);
            setUser(null);
            setEstablishments([]);
            router.push(routes.Login());
        }
    };

    const refreshUser = async (): Promise<UserModel | null> => loadUser();

    const value: AuthContextValue = {
        user,
        establishments,
        isLoading,
        isLoaded: !isLoading && !!user,
        isAuthenticated,
        hasEstablishment: establishments.length > 0,
        logout,
        refreshUser,
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error("useAuth must be used within an AuthProvider");
    }
    return context;
}
