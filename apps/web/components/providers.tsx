import * as React from "react";
import { ThemeProvider as NextThemesProvider } from "next-themes";
import { AuthProvider } from "@/features/auth/hooks/use-auth";
import { TooltipProvider } from "@workspace/ui/components/tooltip";
import { Toaster } from "@workspace/ui/components/sonner";

export async function Providers({
    children,
    initialIsAuthenticated,
}: {
    children: React.ReactNode;
    initialIsAuthenticated: boolean;
}) {
    return (
        <NextThemesProvider attribute="class" defaultTheme="light" enableSystem enableColorScheme>
            <TooltipProvider>
                <AuthProvider initialIsAuthenticated={initialIsAuthenticated}>
                    {children}
                </AuthProvider>
            </TooltipProvider>
            <Toaster />
        </NextThemesProvider>
    );
}
