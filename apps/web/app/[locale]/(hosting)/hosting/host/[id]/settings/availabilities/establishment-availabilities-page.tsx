"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentAvailabilitiesList } from "@/features/establishments/components/establishment-availabilities-list";

export default function EstablishmentAvailabilitiesPage() {
    const { params } = useNavigation<{ id: string }>();

    return <EstablishmentAvailabilitiesList establishmentId={params.id} />;
}
