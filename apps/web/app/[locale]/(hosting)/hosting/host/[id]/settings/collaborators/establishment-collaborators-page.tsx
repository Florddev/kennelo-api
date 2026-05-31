"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentCollaboratorsTable } from "@/features/establishments/components/establishment-collaborators-table";

export default function EstablishmentCollaboratorsPage() {
    const { params } = useNavigation<{ id: string }>();

    return <EstablishmentCollaboratorsTable establishmentId={params.id} />;
}
