"use client";

import { Skeleton } from "@workspace/ui/components/skeleton";

import { GeneralEditForm } from "@/features/pets/components/forms/edit/general-edit-form";
import { usePet } from "@/features/pets/hooks/use-pet";
import { useRouteParams } from "@/hooks/use-route-params";

function GeneralEditSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <Skeleton className="h-20 rounded-sm" />

            <Skeleton className="h-4 w-36 rounded mt-4" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <div className="grid md:grid-cols-2 gap-3">
                <Skeleton className="h-12 md:h-16 rounded-sm" />
                <Skeleton className="h-12 md:h-16 rounded-sm" />
            </div>
            <Skeleton className="h-12 md:h-16 rounded-sm" />

            <Skeleton className="h-4 w-28 rounded mt-4" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />

            <Skeleton className="h-4 w-40 rounded mt-4" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-24 rounded-sm" />

            <Skeleton className="h-12 rounded-4xl mt-2" />
        </div>
    );
}

export function PetEditGeneralPage() {
    const { id } = useRouteParams<{ id: string }>();
    const { pet, isLoading } = usePet(id);

    if (isLoading || !pet) return <GeneralEditSkeleton />;
    return <GeneralEditForm pet={pet} />;
}
