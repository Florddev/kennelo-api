"use client";

import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";

import { useNavigation } from "@/hooks/use-navigation";

export default function HomeFooter() {
    const t = useTranslations();
    const { routes } = useNavigation();
    const year = new Date().getFullYear();

    const navigationLinks = [
        { href: routes.Explore(), label: t("features.explore.title") },
        { href: routes.BecomeHost(), label: t("common.actions.becomeHost") },
    ];

    const legalLinks = [
        { href: routes.Cgu(), label: t("features.legal.cgu.title") },
        { href: routes.Cgv(), label: t("features.legal.cgv.title") },
        { href: routes.Confidentialite(), label: t("features.legal.confidentialite.title") },
    ];

    return (
        <footer
            data-slot="home-footer"
            className="flex flex-col gap-8 border-t border-border pt-10"
        >
            <div className="container mx-auto ">
                <div className="flex flex-col gap-8 md:flex-row md:justify-between">
                    <div className="flex flex-col items-start gap-3">
                        <Image
                            src="/logo_type.svg"
                            alt="Kennelo"
                            width={200}
                            height={38}
                            className="h-8 w-auto"
                        />
                        <p className="max-w-xs text-sm text-muted-foreground">
                            {t("features.home.footer.tagline")}
                        </p>
                    </div>

                    <div className="flex gap-16">
                        <nav className="flex flex-col gap-3">
                            <span className="text-sm font-semibold">
                                {t("features.home.footer.navigation")}
                            </span>
                            {navigationLinks.map(({ href, label }) => (
                                <Link
                                    key={href}
                                    href={href}
                                    className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {label}
                                </Link>
                            ))}
                        </nav>

                        <nav className="flex flex-col gap-3">
                            <span className="text-sm font-semibold">
                                {t("features.home.footer.legal")}
                            </span>
                            {legalLinks.map(({ href, label }) => (
                                <Link
                                    key={href}
                                    href={href}
                                    className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {label}
                                </Link>
                            ))}
                        </nav>
                    </div>
                </div>

                <p className="text-xs text-muted-foreground">
                    © {year} Kennelo. {t("features.home.footer.rights")}
                </p>
            </div>
        </footer>
    );
}
