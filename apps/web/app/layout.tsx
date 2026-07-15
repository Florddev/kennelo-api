import "@workspace/ui/globals.css";
import { Geist_Mono, Plus_Jakarta_Sans, Bricolage_Grotesque } from "next/font/google"; // Fraunces
import { Providers } from "@/components/providers";
import { Suspense } from "react";
import { getLocale, getTranslations } from "next-intl/server";
import { LocaleDirection } from "@/dictionaries";
import { routing } from "@/lib/i18n/routing";
import { cn } from "@workspace/ui/lib/utils";

const fontHeading = Bricolage_Grotesque({
    subsets: ["latin"],
    variable: "--font-heading",
    display: "swap",
});

const fontSans = Plus_Jakarta_Sans({
    subsets: ["latin"],
    variable: "--font-sans",
    display: "swap",
});

const fontMono = Geist_Mono({
    subsets: ["latin"],
    variable: "--font-mono",
    display: "swap",
});

async function getInitialAuthState(): Promise<boolean> {
    if (process.env.NEXT_PUBLIC_ROUTE_MODE === "static") return false;
    const { cookies } = await import("next/headers");
    const cookieStore = await cookies();
    return cookieStore.has("access_token");
}

async function getRootLocale(): Promise<string> {
    if (process.env.NEXT_PUBLIC_ROUTE_MODE === "static") return routing.defaultLocale;
    return getLocale();
}

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
    const initialIsAuthenticated = await getInitialAuthState();
    const locale = await getRootLocale();
    const t = await getTranslations({ locale });
    const dir = t("settings.dir") as LocaleDirection;

    return (
        <html lang={locale} dir={dir} data-scroll-behavior="smooth" suppressHydrationWarning>
            <head>
                {process.env.NODE_ENV === "development" &&
                    process.env.NEXT_PUBLIC_PLATFORM !== "mobile" && (
                        <script
                            async
                            crossOrigin="anonymous"
                            src="https://tweakcn.com/live-preview.min.js"
                        />
                    )}
            </head>
            <body
                className={cn(
                    fontSans.variable,
                    fontMono.variable,
                    fontHeading.variable,
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
