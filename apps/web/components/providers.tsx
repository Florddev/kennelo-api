"use client";

import * as React from "react";
import { ThemeProvider as NextThemesProvider } from "next-themes";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { TooltipProvider } from "@workspace/ui/components/tooltip";
import { Toaster } from "@workspace/ui/components/sonner";
import { AuthProvider } from "@/features/auth/hooks/use-auth";
import { WebsocketProvider } from "@/providers/websocket-provider";

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

    return (
        <NextThemesProvider attribute="class" defaultTheme="light" enableSystem enableColorScheme>
            <TooltipProvider>
                <QueryClientProvider client={queryClient}>
                    <AuthProvider initialIsAuthenticated={initialIsAuthenticated}>
                        {children}
                        <WebsocketProvider />
                    </AuthProvider>
                </QueryClientProvider>
            </TooltipProvider>
            <Toaster />
        </NextThemesProvider>
    );
}
