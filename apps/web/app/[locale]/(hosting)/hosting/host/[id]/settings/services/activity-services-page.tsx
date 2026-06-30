"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery } from "@tanstack/react-query";
import { AddCircle } from "@solar-icons/react";

import { getAnimalTypes } from "@workspace/modules/pets";
import type { ServiceModel } from "@workspace/modules/services";
import { Button } from "@workspace/ui/components/button";
import { Badge } from "@workspace/ui/components/badge";
import { Card } from "@workspace/ui/components/card";

import { useNavigation } from "@/hooks/use-navigation";
import { useActivityServices } from "@/features/activities/hooks/use-activity-services";
import { ServiceDialog } from "@/features/activities/components/services/service-dialog";

export default function ActivityServicesPage() {
    const t = useTranslations();
    const { params } = useNavigation<{ id: string }>();
    const activityId = params.id;

    const { services, isLoading } = useActivityServices(activityId);
    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<ServiceModel | null>(null);

    if (isLoading) {
        return null;
    }

    const animalTypeName = (id: string) => animalTypes.find((type) => type.id === id)?.name ?? "";

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (service: ServiceModel) => {
        setEditing(service);
        setDialogOpen(true);
    };

    return (
        <div className="flex flex-col gap-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold">{t("features.activities.services.title")}</h2>
                <Button className="gap-1.5 rounded-4xl" onClick={openCreate}>
                    <AddCircle className="size-4" />
                    {t("features.activities.services.addService")}
                </Button>
            </div>

            {services.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t("features.activities.services.empty")}
                </p>
            ) : (
                <div className="flex flex-col gap-2">
                    {services.map((service) => (
                        <Card
                            key={service.id}
                            role="button"
                            tabIndex={0}
                            onClick={() => openEdit(service)}
                            className="flex cursor-pointer flex-row items-center justify-between p-4 transition-colors hover:bg-muted"
                        >
                            <div className="flex flex-col gap-0.5">
                                <span className="font-medium">{service.name}</span>
                                <span className="text-sm text-muted-foreground">
                                    {animalTypeName(service.animalTypeId)}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                {service.isIncluded ? (
                                    <Badge variant="secondary">
                                        {t("features.activities.services.includedBadge")}
                                    </Badge>
                                ) : null}
                                <span className="text-sm font-medium">{service.price} €</span>
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            <ServiceDialog
                activityId={activityId}
                service={editing}
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </div>
    );
}
