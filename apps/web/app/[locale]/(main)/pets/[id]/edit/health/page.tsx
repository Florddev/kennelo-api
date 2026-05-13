"use client";

import { useParams } from "next/navigation";
import { usePet } from "@/features/pets/hooks/use-pet";
import { HealthEditForm } from "@/features/pets/components/forms/edit/health-edit-form";

export default function PetEditHealthPage() {
    const { id } = useParams<{ id: string }>();
    const { pet } = usePet(id);

    return pet && <HealthEditForm pet={pet} />;
}
