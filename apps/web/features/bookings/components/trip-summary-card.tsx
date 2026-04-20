import Image from "next/image";
import { Star } from "lucide-react";
import type { EstablishmentModel } from "@workspace/modules/establishments";

import { demoImage, demoRating } from "@/features/explore";

type TripSummaryCardProps = {
    establishment: EstablishmentModel;
};

export function TripSummaryCard({ establishment }: TripSummaryCardProps) {
    const image = demoImage(establishment.id, 400, 400, 1);
    const rating = demoRating(establishment.id);

    return (
        <div
            data-slot="trip-summary-card"
            className="flex items-center gap-3 rounded-2xl border p-3"
        >
            <div className="relative size-16 shrink-0 overflow-hidden rounded-xl">
                <Image
                    src={image}
                    alt={establishment.name}
                    fill
                    sizes="64px"
                    className="object-cover"
                />
            </div>
            <div className="flex flex-1 flex-col gap-1 min-w-0">
                <p className="truncate text-sm font-semibold text-slate-900">
                    {establishment.name}
                </p>
                {establishment.address && (
                    <p className="truncate text-xs text-muted-foreground">
                        {establishment.address.city}, {establishment.address.country}
                    </p>
                )}
                <div className="flex items-center gap-1 text-xs">
                    <Star className="size-3 fill-amber-400 stroke-amber-400" />
                    <span className="font-semibold text-slate-800">{rating}</span>
                </div>
            </div>
        </div>
    );
}
