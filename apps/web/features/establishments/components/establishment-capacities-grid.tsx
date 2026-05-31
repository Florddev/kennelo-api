"use client";

import { useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Plus, PawPrint } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { NumberStepper } from "@workspace/ui/components/number-stepper";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@workspace/ui/components/dialog";
import {
    Drawer,
    DrawerContent,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";
import { getCapacities, createCapacity } from "@workspace/modules/establishments";
import { getAnimalTypes } from "@workspace/modules/pets";

import { useAsyncState } from "@/hooks/use-async-state";
import { useIsMobile } from "@/hooks/use-mobile";
import { CapacityCard } from "./capacity-card";
import { EstablishmentPageHeader } from "./establishment-page-header";

const DEFAULT_CAPACITY = 10;
const DEFAULT_PRICE = 20;
const MAX_CAPACITY = 999;
const MAX_PRICE = 9999;

export function EstablishmentCapacitiesGrid({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();

    const { data: capacities = [], isLoading } = useQuery({
        queryKey: ["establishment-capacities", establishmentId],
        queryFn: () => getCapacities(establishmentId),
        staleTime: 60_000,
        enabled: Boolean(establishmentId),
    });

    if (isLoading) {
        return null;
    }

    return (
        <div className="flex flex-col gap-6">
            <EstablishmentPageHeader>
                <AddCapacity
                    establishmentId={establishmentId}
                    usedAnimalTypeIds={capacities.map((capacity) => capacity.animalType.id)}
                />
            </EstablishmentPageHeader>

            {capacities.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed py-16 text-center">
                    <div className="mb-4 flex size-12 items-center justify-center rounded-full bg-muted">
                        <PawPrint className="size-6 text-muted-foreground" />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {t("features.establishments.capacities.empty")}
                    </p>
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    {capacities.map((capacity) => (
                        <CapacityCard
                            key={capacity.id}
                            capacity={capacity}
                            establishmentId={establishmentId}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

function AddCapacity({
    establishmentId,
    usedAnimalTypeIds,
}: {
    establishmentId: string;
    usedAnimalTypeIds: string[];
}) {
    const t = useTranslations();
    const isMobile = useIsMobile();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();
    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    const [open, setOpen] = useState(false);
    const [animalTypeId, setAnimalTypeId] = useState<string>("");
    const [maxCapacity, setMaxCapacity] = useState(DEFAULT_CAPACITY);
    const [pricePerNight, setPricePerNight] = useState(DEFAULT_PRICE);

    const availableTypes = useMemo(
        () => animalTypes.filter((type) => !usedAnimalTypeIds.includes(String(type.id))),
        [animalTypes, usedAnimalTypeIds],
    );

    const resetForm = () => {
        setAnimalTypeId("");
        setMaxCapacity(DEFAULT_CAPACITY);
        setPricePerNight(DEFAULT_PRICE);
    };

    const handleSubmit = async () => {
        if (!animalTypeId) {
            return;
        }
        const result = await execute(
            () =>
                createCapacity(establishmentId, {
                    animalTypeId,
                    maxCapacity,
                    pricePerNight,
                }),
            {
                onSuccess: () => {
                    queryClient.invalidateQueries({
                        queryKey: ["establishment-capacities", establishmentId],
                    });
                },
            },
        );
        if (result) {
            resetForm();
            setOpen(false);
        }
    };

    const title = t("features.establishments.capacities.addCapacity");

    const trigger = (
        <Button className="gap-1.5 rounded-4xl" disabled={availableTypes.length === 0}>
            <Plus className="size-4" />
            {title}
        </Button>
    );

    const form = (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-1.5">
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {t("common.fields.animalType")}
                </span>
                <Select value={animalTypeId} onValueChange={setAnimalTypeId}>
                    <SelectTrigger>
                        <SelectValue placeholder={t("common.placeholders.selectAnimalType")} />
                    </SelectTrigger>
                    <SelectContent>
                        {availableTypes.map((type) => (
                            <SelectItem key={type.id} value={type.id.toString()}>
                                {type.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <NumberStepper
                label={t("common.fields.maxCapacity")}
                value={maxCapacity}
                onIncrement={() => setMaxCapacity(maxCapacity + 1)}
                onDecrement={() => setMaxCapacity(maxCapacity - 1)}
                min={1}
                max={MAX_CAPACITY}
            />
            <NumberStepper
                label={t("common.fields.pricePerNight")}
                value={pricePerNight}
                onIncrement={() => setPricePerNight(pricePerNight + 1)}
                onDecrement={() => setPricePerNight(pricePerNight - 1)}
                min={0}
                max={MAX_PRICE}
                formatValue={(price) =>
                    t("features.establishments.capacities.priceValue", { price })
                }
            />
        </div>
    );

    const submitButton = (
        <Button
            type="button"
            className="rounded-4xl"
            onClick={handleSubmit}
            disabled={isLoading || !animalTypeId}
        >
            {isLoading ? t("common.actions.loading") : t("common.actions.create")}
        </Button>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader className="text-start">
                        <DrawerTitle>{title}</DrawerTitle>
                    </DrawerHeader>
                    <div className="px-4">{form}</div>
                    <DrawerFooter>{submitButton}</DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>
                {form}
                <DialogFooter>{submitButton}</DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
