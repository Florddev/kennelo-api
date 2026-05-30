"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentCapacitiesGrid } from "@/features/establishments/components/establishment-capacities-grid";

export default function EstablishmentCapacitiesPage() {
    const { params } = useNavigation<{ id: string }>();

    return <EstablishmentCapacitiesGrid establishmentId={params.id} />;
}
