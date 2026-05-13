"use client";

import { useParams } from "next/navigation";
import { usePet } from "@/features/pets/hooks/use-pet";
import { PersonalityEditForm } from "@/features/pets/components/forms/edit/personality-edit-form";

export default function PetEditPersonalityPage() {
    const { id } = useParams<{ id: string }>();
    const { pet } = usePet(id);

    return pet && <PersonalityEditForm pet={pet} />;
}
