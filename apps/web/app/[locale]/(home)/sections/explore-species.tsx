"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";
import { Card } from "@workspace/ui/components/card";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { useNavigation } from "@/hooks/use-navigation";

const SPECIES = [
    "dog",
    "cat",
    "rabbit",
    "rodent",
    "ferret",
    "bird",
    "reptile",
    "amphibian",
] as const;

const COLOR_MAPPED_TYPES = new Set(["dog", "cat", "bird", "rabbit", "reptile", "amphibian"]);

function SpeciesCard({ type, label }: { type: string; label: string }) {
    const illustrated = isIllustratedType(type);
    const hasColor = COLOR_MAPPED_TYPES.has(type);

    return (
        <Card
            className={cn(
                "w-28 shrink-0 py-5 transition-transform hover:scale-[1.03]",
                hasColor ? `bg-${type}-50` : "bg-muted",
            )}
        >
            <div className="flex flex-col items-center gap-3 text-center">
                {illustrated ? (
                    <PetTypeIllustration code={type} className="size-10" />
                ) : (
                    <PawPrint className="size-10 text-muted-foreground" strokeWidth={1.5} />
                )}
                <span className="text-sm font-medium">{label}</span>
            </div>
        </Card>
    );
}

export default function ExploreSpecies() {
    const t = useTranslations();
    const { routes } = useNavigation();

    const species = SPECIES.map((type) => ({
        type,
        label: t(`features.pets.types.${type}`),
    }));

    return (
        <section data-slot="explore-species" className="flex flex-col gap-5">
            <div className="flex flex-col gap-1 text-center">
                <h2 className="text-2xl font-bold font-heading tracking-tight">
                    {t("features.home.species.title")}
                </h2>
                <p className="text-muted-foreground text-sm">
                    {t("features.home.species.subtitle")}
                </p>
            </div>

            <div className="-mx-8 flex gap-4 overflow-x-auto scrollbar-none px-4 pb-1 lg:hidden">
                {species.map(({ type, label }) => (
                    <Link key={type} href={routes.Explore()}>
                        <SpeciesCard type={type} label={label} />
                    </Link>
                ))}
            </div>

            <div
                data-slot="explore-species-marquee"
                className="group relative hidden overflow-hidden lg:block [mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]"
            >
                <div className="flex w-max gap-4 animate-marquee group-hover:[animation-play-state:paused]">
                    {[...species, ...species].map(({ type, label }, index) => (
                        <Link key={`${type}-${index}`} href={routes.Explore()}>
                            <SpeciesCard type={type} label={label} />
                        </Link>
                    ))}
                </div>
            </div>
        </section>
    );
}
