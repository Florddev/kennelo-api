"use client";

import { useTranslations } from "next-intl";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentInfoSection } from "@/features/establishments/components/establishment-info-section";
import { EstablishmentPageHeader } from "@/features/establishments/components/establishment-page-header";

export default function EstablishmentInfoPage() {
    const t = useTranslations();
    const { params } = useNavigation<{ id: string }>();

    return (
        <div className="flex flex-col gap-6">
            <EstablishmentPageHeader />
            <EstablishmentInfoSection establishmentId={params.id} t={t} />
        </div>
    );
}
