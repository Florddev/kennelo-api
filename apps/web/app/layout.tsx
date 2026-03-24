import "@workspace/ui/globals.css";
import { Geist_Mono, Bricolage_Grotesque } from "next/font/google";
import { Providers } from "@/components/providers";
import { Suspense } from "react";
import { DEFAULT_LOCALE, DEFAULT_LOCALE_DIR } from "@/dictionaries";
import { cn } from "@workspace/ui/lib/utils";
import { cookies } from "next/headers";

const fontSans = Bricolage_Grotesque({
    subsets: ["latin"],
    variable: "--font-sans",
});

const fontMono = Geist_Mono({
    subsets: ["latin"],
    variable: "--font-mono",
});

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
    const cookieStore = await cookies();
    const initialIsAuthenticated = cookieStore.has("access_token");

    return (
        <html lang={DEFAULT_LOCALE} dir={DEFAULT_LOCALE_DIR} suppressHydrationWarning>
            <head>
                <script
                    async
                    crossOrigin="anonymous"
                    src="https://tweakcn.com/live-preview.min.js"
                />
            </head>
            <body
                className={cn(
                    fontSans.variable,
                    fontMono.variable,
                    "font-sans antialiased bg-background text-foreground",
                )}
                suppressHydrationWarning
            >
                <Providers initialIsAuthenticated={initialIsAuthenticated}>
                    <Suspense>{children}</Suspense>
                </Providers>
            </body>
        </html>
    );
}
