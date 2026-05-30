"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentBookingsTable } from "@/features/establishments/components/establishment-bookings-table";

export default function EstablishmentBookingsPage() {
    const { params } = useNavigation<{ id: string }>();

    return <EstablishmentBookingsTable establishmentId={params.id} />;
}
