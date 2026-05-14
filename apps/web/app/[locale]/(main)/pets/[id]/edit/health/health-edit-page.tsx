"use client";

import { Skeleton } from "@workspace/ui/components/skeleton";

import { HealthEditForm } from "@/features/pets/components/forms/edit/health-edit-form";
import { usePet } from "@/features/pets/hooks/use-pet";
import { useRouteParams } from "@/hooks/use-route-params";

function HealthEditSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-24 rounded-sm" />

            <Skeleton className="h-12 rounded-4xl mt-2" />
        </div>
    );
}

export function PetEditHealthPage() {
    const { id } = useRouteParams<{ id: string }>();
    const { pet, isLoading } = usePet(id);

    if (isLoading || !pet) return <HealthEditSkeleton />;
    return <HealthEditForm pet={pet} />;
}
