"use client";

import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";
import { useNavigation } from "@/hooks/use-navigation";

export default function BecomeHostCta() {
    const t = useTranslations();
    const { routes } = useNavigation();

    return (
        <section
            data-slot="become-host-cta"
            className="relative flex flex-col items-center gap-6 overflow-hidden rounded-3xl bg-secondary/20 p-8 md:flex-row"
        >
            <span
                aria-hidden
                className="pointer-events-none absolute -top-10 -start-10 h-40 w-48 rounded-[50%] bg-secondary/60"
            />
            <div className="relative flex flex-1 flex-col items-center gap-3 self-center text-center md:items-start md:text-start">
                <h2 className="text-2xl font-bold font-heading tracking-tight">
                    {t("features.home.become-host.title")}
                </h2>
                <p className="max-w-md text-muted-foreground">
                    {t("features.home.become-host.subtitle")}
                </p>
                <Button asChild size="lg" className="mt-1">
                    <Link href={routes.BecomeHost()}>{t("features.home.become-host.cta")}</Link>
                </Button>
            </div>
            <Image
                src="/keny_illustration.png"
                alt=""
                aria-hidden
                width={160}
                height={120}
                className="relative h-auto w-36 shrink-0 self-center object-contain md:w-40"
            />
        </section>
    );
}
