"use client";

import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { UserModel } from "@workspace/modules/users";
import { getCurrentUser, logoutUser, authService, refreshToken } from "@workspace/modules/users";
import { getActivities, ActivityModel } from "@workspace/modules/activities";
import { api } from "@workspace/common";
import { useRouter } from "next/navigation";
import posthog from "posthog-js";
import { logger } from "@/lib/logger";
import { routes } from "@/lib/routes";
import { getAppStorage } from "@/lib/storage";

interface AuthContextValue {
    user: UserModel | null;
    activities: ActivityModel[];
    isLoading: boolean;
    isLoaded: boolean;
    isAuthenticated: boolean;
    hasActivity: boolean;
    logout: () => Promise<void>;
    refreshUser: () => Promise<UserModel | null>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

let refreshPromise: Promise<string | null> | null = null;

async function getFreshAccessToken(): Promise<string | null> {
    const token = await authService.getAccessToken();

    if (token && !(await authService.isAccessTokenExpired())) {
        return token;
    }

    if (!(await authService.getRefreshToken())) {
        return token;
    }

    if (!refreshPromise) {
        refreshPromise = refreshToken()
            .catch(() => null)
            .finally(() => {
                refreshPromise = null;
            });
    }

    return refreshPromise;
}

export function AuthProvider({
    children,
    initialIsAuthenticated = false,
}: {
    children: ReactNode;
    initialIsAuthenticated?: boolean;
}) {
    const [user, setUser] = useState<UserModel | null>(null);
    const [activities, setActivities] = useState<ActivityModel[]>([]);
    const [isLoading, setIsLoading] = useState(initialIsAuthenticated);
    const [isAuthenticated, setIsAuthenticated] = useState(initialIsAuthenticated);
    const queryClient = useQueryClient();
    const router = useRouter();

    useState(() => {
        authService.configure(getAppStorage());
        api.setTokenGetter(() => getFreshAccessToken());
    });

    const loadUser = async (): Promise<UserModel | null> => {
        if (!(await authService.isAuthenticated())) {
            setIsAuthenticated(false);
            setUser(null);
            setActivities([]);
            setIsLoading(false);
            return null;
        }

        setIsAuthenticated(true);

        try {
            const [currentUser, userActivities] = await Promise.all([
                getCurrentUser(),
                getActivities().catch(() => []),
            ]);
            setUser(currentUser);
            setActivities(userActivities);
            if (currentUser) {
                posthog.identify(currentUser.id, {
                    email: currentUser.email,
                    firstName: currentUser.firstName,
                    lastName: currentUser.lastName,
                    locale: currentUser.locale,
                });
            }
            return currentUser;
        } catch (error) {
            logger.error("Failed to load user:", error);
            const status = (error as { status?: number })?.status;
            if (status === 401 || (await authService.isAccessTokenExpired())) {
                setIsAuthenticated(false);
                setUser(null);
                setActivities([]);
                await authService.clearTokens();
            }
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
            posthog.capture("user_logged_out");
            posthog.reset();
            queryClient.clear();
            setIsAuthenticated(false);
            setUser(null);
            setActivities([]);
            router.push(routes.Login());
        }
    };

    const refreshUser = async (): Promise<UserModel | null> => loadUser();

    const value: AuthContextValue = {
        user,
        activities,
        isLoading,
        isLoaded: !isLoading && !!user,
        isAuthenticated,
        hasActivity: activities.length > 0,
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
