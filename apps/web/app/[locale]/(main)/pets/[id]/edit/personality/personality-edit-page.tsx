"use client";

import { Skeleton } from "@workspace/ui/components/skeleton";

import { PersonalityEditForm } from "@/features/pets/components/forms/edit/personality-edit-form";
import { usePet } from "@/features/pets/hooks/use-pet";
import { useRouteParams } from "@/hooks/use-route-params";

function PersonalityEditSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <Skeleton className="h-4 w-32 rounded mt-4" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />

            <Skeleton className="h-4 w-28 rounded mt-4" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />

            <Skeleton className="h-4 w-36 rounded mt-4" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />
            <Skeleton className="h-12 md:h-16 rounded-sm" />

            <Skeleton className="h-12 rounded-4xl mt-2" />
        </div>
    );
}

export function PetEditPersonalityPage() {
    const { id } = useRouteParams<{ id: string }>();
    const { pet, isLoading } = usePet(id);

    if (isLoading || !pet) return <PersonalityEditSkeleton />;
    return <PersonalityEditForm pet={pet} />;
}
