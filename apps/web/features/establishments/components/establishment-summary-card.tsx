import { Building2 } from "lucide-react";
import type { EstablishmentModel } from "@workspace/modules/establishments";

type EstablishmentSummaryCardProps = {
    establishment: EstablishmentModel;
};

export function EstablishmentSummaryCard({ establishment }: EstablishmentSummaryCardProps) {
    return (
        <div
            data-slot="establishment-summary-card"
            className="flex items-center gap-3 rounded-2xl border p-3"
        >
            <div className="flex size-16 shrink-0 items-center justify-center rounded-xl bg-muted">
                <Building2 className="size-7 text-muted-foreground" />
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
            </div>
        </div>
    );
}
