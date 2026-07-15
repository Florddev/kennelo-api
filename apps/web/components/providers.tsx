"use client";

import * as React from "react";
import { ThemeProvider as NextThemesProvider } from "next-themes";
import { GoogleOAuthProvider } from "@react-oauth/google";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { TooltipProvider } from "@workspace/ui/components/tooltip";
import { Toaster } from "@workspace/ui/components/sonner";
import { AuthProvider } from "@/features/auth/hooks/use-auth";
import { NavigationVisibilityProvider } from "@/providers/navigation-visibility-provider";
import { WebsocketProvider } from "@/providers/websocket-provider";
import { CookieConsentBanner } from "@/components/cookie-consent-banner";

export function Providers({
    children,
    initialIsAuthenticated,
}: {
    children: React.ReactNode;
    initialIsAuthenticated: boolean;
}) {
    const [queryClient] = React.useState(
        () =>
            new QueryClient({
                defaultOptions: {
                    queries: {
                        staleTime: 5 * 60 * 1000,
                        gcTime: 30 * 60 * 1000,
                        refetchOnWindowFocus: false,
                    },
                },
            }),
    );

    const googleClientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID ?? "";

    const tree = (
        <NextThemesProvider attribute="class" defaultTheme="light" enableSystem enableColorScheme>
            <TooltipProvider>
                <QueryClientProvider client={queryClient}>
                    <AuthProvider initialIsAuthenticated={initialIsAuthenticated}>
                        <NavigationVisibilityProvider>{children}</NavigationVisibilityProvider>
                        <WebsocketProvider />
                    </AuthProvider>
                </QueryClientProvider>
            </TooltipProvider>
            <Toaster />
            <CookieConsentBanner />
        </NextThemesProvider>
    );

    return <GoogleOAuthProvider clientId={googleClientId}>{tree}</GoogleOAuthProvider>;
}
