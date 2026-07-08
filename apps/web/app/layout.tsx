import "@workspace/ui/globals.css";
import { Geist_Mono, Plus_Jakarta_Sans, Bricolage_Grotesque } from "next/font/google"; // Fraunces
import { Providers } from "@/components/providers";
import { Suspense } from "react";
import { DEFAULT_LOCALE, DEFAULT_LOCALE_DIR } from "@/dictionaries";
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

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
    const initialIsAuthenticated = await getInitialAuthState();

    return (
        <html
            lang={DEFAULT_LOCALE}
            dir={DEFAULT_LOCALE_DIR}
            data-scroll-behavior="smooth"
            suppressHydrationWarning
        >
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
