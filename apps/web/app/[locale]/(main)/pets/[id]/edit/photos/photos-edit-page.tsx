"use client";

import { Skeleton } from "@workspace/ui/components/skeleton";

import { PhotosEditForm } from "@/features/pets/components/forms/edit/photos-edit-form";
import { usePet } from "@/features/pets/hooks/use-pet";
import { useRouteParams } from "@/hooks/use-route-params";

function PhotosEditSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <Skeleton className="h-4 w-32 rounded mt-4" />
            <div className="grid grid-cols-3 md:grid-cols-4 gap-2">
                <Skeleton className="aspect-square rounded-sm" />
                <Skeleton className="aspect-square rounded-sm" />
                <Skeleton className="aspect-square rounded-sm" />
                <Skeleton className="aspect-square rounded-sm hidden md:block" />
            </div>
        </div>
    );
}

export function PetEditPhotosPage() {
    const { id } = useRouteParams<{ id: string }>();
    const { pet, isLoading } = usePet(id);

    if (isLoading || !pet) return <PhotosEditSkeleton />;
    return <PhotosEditForm pet={pet} />;
}
