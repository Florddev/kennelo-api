"use client";

import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";
import { ChoiceCardLabel } from "@workspace/ui/components/choice-cards";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { BackgroundShapeSvg } from "@/components/svg/background-shape";
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

function shapeRotation(type: string) {
    let hash = 0;
    for (let i = 0; i < type.length; i++) {
        hash = (hash * 31 + type.charCodeAt(i)) % 360;
    }
    return hash;
}

function SpeciesCard({ type, label }: { type: string; label: string }) {
    const illustrated = isIllustratedType(type);

    return (
        <div className="relative group/card w-40 shrink-0 overflow-hidden flex flex-col items-start justify-between gap-6 rounded-lg border border-input py-4 px-5 text-start transition-all bg-card">
            {illustrated ? (
                <div className="relative w-full h-24 group-hover/card:scale-115 transition-transform z-10">
                    <Image
                        src={`/illustrations/pets/${type}.svg`}
                        alt={label}
                        className="object-contain"
                        fill
                    />
                </div>
            ) : (
                <div className="relative w-full h-24 flex items-center justify-center group-hover/card:scale-115 transition-transform z-10">
                    <PawPrint className="size-12 text-primary" />
                </div>
            )}
            <div className="flex flex-col gap-1 justify-center items-center w-full z-10">
                <ChoiceCardLabel className="text-xl">{label}</ChoiceCardLabel>
            </div>
            <BackgroundShapeSvg
                className={cn(
                    "absolute top-0 -start-1/2 translate-x-1/2 -translate-y-1/2 text-muted scale-125",
                    "z-0 group-hover/card:scale-120 transition-all duration-300",
                )}
                style={{ transform: `rotate(${shapeRotation(type)}deg)` }}
            />
        </div>
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
            <div className="flex flex-col gap-2 text-center">
                <h2 className="text-4xl font-bold tracking-tight">
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
