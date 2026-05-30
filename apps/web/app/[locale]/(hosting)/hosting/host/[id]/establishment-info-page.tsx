"use client";

import { useTranslations } from "next-intl";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentInfoSection } from "@/features/establishments/components/establishment-info-section";

export default function EstablishmentInfoPage() {
    const t = useTranslations();
    const { params } = useNavigation<{ id: string }>();

    return <EstablishmentInfoSection establishmentId={params.id} t={t} />;
}
