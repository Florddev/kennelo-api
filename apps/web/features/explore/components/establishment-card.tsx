import Link from "next/link";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { Star } from "lucide-react";
import { EstablishmentModel } from "@workspace/modules/establishments";
import { cn } from "@workspace/ui/lib/utils";

import { demoImage, demoRating, demoReviewsCount, demoFallbackPrice } from "../lib/demo-values";
import { HeartButton } from "./heart-button";

type EstablishmentCardProps = {
    establishment: EstablishmentModel;
    href: string;
    className?: string;
};

function formatReviewsCompact(count: number): string {
    if (count >= 1000) {
        return `${(count / 1000).toFixed(1)}k`;
    }
    return String(count);
}

export function EstablishmentCard({ establishment, href, className }: EstablishmentCardProps) {
    const t = useTranslations();
    const rating = demoRating(establishment.id);
    const reviewsCount = demoReviewsCount(establishment.id);
    const reviewsLabel = t("features.explore.reviewsShort", { count: reviewsCount });
    const price = demoFallbackPrice(establishment.id);
    const image = demoImage(establishment.id, 600, 400);
    const subtitle =
        establishment.description ??
        t("features.explore.acceptsAnimals", {
            animals: `${t("features.explore.animals.dogs")} & ${t("features.explore.animals.cats")}`,
        });

    return (
        <Link
            href={href}
            data-slot="establishment-card"
            className={cn(
                "block overflow-hidden rounded-3xl bg-white transition-shadow hover:shadow-lg",
                className,
            )}
        >
            <div className="relative h-64 w-full overflow-hidden rounded-3xl">
                <Image
                    src={image}
                    alt={establishment.name}
                    fill
                    sizes="(max-width: 768px) 100vw, 400px"
                    className="object-cover"
                />
                <div className="absolute top-3 end-3">
                    <HeartButton />
                </div>
            </div>
            <div className="flex flex-col gap-3 px-3 py-4">
                <div className="flex flex-col gap-2">
                    <h3 className="text-xl font-semibold text-black line-clamp-1">
                        {establishment.name}
                    </h3>
                    <p className="text-[15px] font-medium text-slate-500 line-clamp-1">
                        {subtitle}
                    </p>
                </div>
                <div className="flex items-end justify-between gap-2">
                    <div className="flex items-center gap-1.5 text-sm text-slate-500">
                        <Star className="size-4 fill-amber-400 stroke-amber-400" />
                        <span className="font-semibold text-slate-800">{rating}</span>
                        <span>
                            ({formatReviewsCompact(reviewsCount)} {reviewsLabel})
                        </span>
                    </div>
                    <p className="text-slate-800 whitespace-nowrap">
                        <span className="text-xl font-bold">{price}€</span>
                        <span className="text-sm font-medium text-slate-500">
                            {t("features.explore.perNight")}
                        </span>
                    </p>
                </div>
            </div>
        </Link>
    );
}
