"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { routes } from "@/lib/routes";
import { Device } from "@capacitor/device";
import { Button } from "@workspace/ui/components/button";

export function NativePage({ platform }: { platform: string }) {
    const [lang, setLang] = useState<string | null>(null);

    useEffect(() => {
        Device.getLanguageCode().then(({ value }) => setLang(value));
    }, []);

    if (!lang) return <p>Loading lang...</p>;

    return (
        <div className="flex flex-col items-center justify-center h-screen gap-4">
            <p>Root Page, lang: {lang}</p>
            <span>Platform: {platform}</span>

            <Button asChild>
                <Link href={routes.Home({ locale: lang })}>Home</Link>
            </Button>

            <Button asChild>
                <Link href={routes.Login({ locale: lang })}>Login</Link>
            </Button>

            <Button asChild>
                <Link href={routes.ScannerConfigPage({ locale: lang })}>Scanner Config</Link>
            </Button>
        </div>
    );
}
