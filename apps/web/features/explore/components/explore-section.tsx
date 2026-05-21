"use client";

import { cn } from "@workspace/ui/lib/utils";
import { HostCard } from "./host-card";
import type { EstablishmentModel } from "@workspace/modules/establishments";

type ExploreSectionProps = {
    title: string;
    hosts: EstablishmentModel[];
    className?: string;
};

export function ExploreSection({ title, hosts, className }: ExploreSectionProps) {
    return (
        <section data-slot="explore-section" className={cn("flex flex-col gap-3", className)}>
            <div className="flex items-center justify-between px-4">
                <h2 className="text-lg font-bold">{title}</h2>
                <button className="text-sm text-foreground underline underline-offset-2 hover:text-muted-foreground transition-colors">
                    Voir tout
                </button>
            </div>
            <div className="flex gap-3 overflow-x-auto scrollbar-none px-4 pb-1">
                {hosts.map((host) => (
                    <HostCard key={host.id} host={host} variant="vertical" />
                ))}
            </div>
        </section>
    );
}
