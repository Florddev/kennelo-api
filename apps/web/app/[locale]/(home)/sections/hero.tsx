"use client";

import { useTranslations } from "next-intl";

import SearchBar from "@/features/search/components/search-bar";
import MobileSearch from "@/features/search/components/mobile/mobile-search";

export default function HomeHero() {
    const t = useTranslations();

    return (
        <section className="flex flex-col justify-center items-center gap-4 h-[65vh]">
            <div className="relative overflow-hidden rounded-3xl flex items-center justify-center">
                <div className="relative z-10 text-center flex flex-col gap-6">
                    <div className="flex flex-col gap-5 text-center pb-8">
                        <div className="inline-flex self-center items-center gap-2 backdrop-blur-sm bg-secondary/20 border border-secondary/80 rounded-full px-4 py-1.5">
                            <span className="text-xs font-semibold tracking-wide text-foreground/80">
                                {t("features.home.badge")}
                            </span>
                        </div>
                        <h1 className="text-5xl lg:text-6xl font-bold font-heading leading-[1.1] tracking-tight">
                            {t("features.home.title")}
                            <br />
                            <span>{t("features.home.title-accent")}</span>
                        </h1>
                        <p className="text-base lg:text-lg text-muted-foreground max-w-lg mx-auto leading-relaxed">
                            {t("features.home.subtitle")}
                        </p>
                    </div>
                </div>
            </div>
            <div className="hidden md:block w-full">
                <SearchBar />
            </div>
            <div className="md:hidden w-full px-4">
                <MobileSearch />
            </div>
        </section>
    );
}
