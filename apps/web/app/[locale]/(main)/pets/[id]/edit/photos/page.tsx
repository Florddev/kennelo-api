"use client";

import { useParams } from "next/navigation";
import { usePet } from "@/features/pets/hooks/use-pet";
import { PhotosEditForm } from "@/features/pets/components/forms/edit/photos-edit-form";

export default function PetEditPhotosPage() {
    const { id } = useParams<{ id: string }>();
    const { pet } = usePet(id);

    return pet && <PhotosEditForm pet={pet} />;
}
