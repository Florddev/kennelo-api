"use client";

import React from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { usePet } from "@/features/pets/hooks/use-pet";
import { GeneralEditForm } from "@/features/pets/components/forms/edit/general-edit-form";

export default function PetEditPage(): React.ReactElement {
    const { id } = useParams<{ id: string }>();
    const t = useTranslations();
    const { pet } = usePet(id);

    return (
        <div className="flex flex-col gap-6">
            <section className="grid gap-4">
                <div>
                    <h2 className="hidden md:block font-semibold tracking-tight text-2xl md:text-3xl">
                        {t("features.pets.edit.sections.general")}
                    </h2>
                </div>
                {pet && <GeneralEditForm pet={pet} />}
            </section>
        </div>
    );
}
