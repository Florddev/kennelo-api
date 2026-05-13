"use client";

import { useParams } from "next/navigation";
import { usePet } from "@/features/pets/hooks/use-pet";
import { GeneralEditForm } from "@/features/pets/components/forms/edit/general-edit-form";

export default function PetEditGeneralPage() {
    const { id } = useParams<{ id: string }>();
    const { pet } = usePet(id);

    return pet && <GeneralEditForm pet={pet} />;
}
