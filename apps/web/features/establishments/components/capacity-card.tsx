"use client";

import { useQueryClient } from "@tanstack/react-query";
import { PawPrint, Trash2 } from "lucide-react";
import { useTranslations } from "next-intl";
import Image from "next/image";
import { useEffect, useRef, useState } from "react";

import {
    deleteCapacity,
    updateCapacity,
    type CapacityModel,
} from "@workspace/modules/establishments";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@workspace/ui/components/alert-dialog";
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { Button } from "@workspace/ui/components/button";
import { NumberStepper } from "@workspace/ui/components/number-stepper";
import { cn } from "@workspace/ui/lib/utils";

import { BackgroundShapeSvg } from "@/components/svg/background-shape";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { useAsyncState } from "@/hooks/use-async-state";
import { useIsMobile } from "@/hooks/use-mobile";

const SAVE_DEBOUNCE_MS = 600;
const MAX_CAPACITY = 999;
const MAX_PRICE = 9999;

export function CapacityCard({
    capacity,
    establishmentId,
}: {
    capacity: CapacityModel;
    establishmentId: string;
}) {
    const t = useTranslations();
    const isMobile = useIsMobile();
    const queryClient = useQueryClient();
    const { execute } = useAsyncState();

    const [maxCapacity, setMaxCapacity] = useState(capacity.maxCapacity);
    const [pricePerNight, setPricePerNight] = useState(capacity.pricePerNight);
    const [syncedValues, setSyncedValues] = useState({
        max: capacity.maxCapacity,
        price: capacity.pricePerNight,
    });
    const saveTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const [rotation] = useState(() => {
        const buf = new Uint32Array(1);
        crypto.getRandomValues(buf);
        return (buf[0]! / 0xffffffff) * 360;
    });

    const normalizedCode = capacity.animalType.code.toLowerCase();

    if (
        syncedValues.max !== capacity.maxCapacity ||
        syncedValues.price !== capacity.pricePerNight
    ) {
        setSyncedValues({ max: capacity.maxCapacity, price: capacity.pricePerNight });
        setMaxCapacity(capacity.maxCapacity);
        setPricePerNight(capacity.pricePerNight);
    }

    const scheduleSave = (nextMaxCapacity: number, nextPrice: number) => {
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }
        saveTimeoutRef.current = setTimeout(() => {
            execute(
                () =>
                    updateCapacity(establishmentId, capacity.id, {
                        maxCapacity: nextMaxCapacity,
                        pricePerNight: nextPrice,
                    }),
                {
                    onSuccess: () => {
                        queryClient.invalidateQueries({
                            queryKey: ["establishment-capacities", establishmentId],
                        });
                    },
                },
            );
        }, SAVE_DEBOUNCE_MS);
    };

    useEffect(
        () => () => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }
        },
        [],
    );

    const updateCapacityValue = (next: number) => {
        setMaxCapacity(next);
        scheduleSave(next, pricePerNight);
    };

    const updatePriceValue = (next: number) => {
        setPricePerNight(next);
        scheduleSave(maxCapacity, next);
    };

    const handleDelete = () =>
        execute(() => deleteCapacity(establishmentId, capacity.id), {
            onSuccess: () => {
                queryClient.invalidateQueries({
                    queryKey: ["establishment-capacities", establishmentId],
                });
            },
        });

    return (
        <div
            data-slot="capacity-card"
            className="group relative flex flex-col overflow-hidden rounded-2xl border bg-card overflow-hidden"
        >
            {isMobile ? (
                <Drawer>
                    <DrawerTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="absolute end-3 top-3 z-20 size-8 text-muted-foreground hover:text-destructive"
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </DrawerTrigger>
                    <DrawerContent>
                        <DrawerHeader className="text-start">
                            <DrawerTitle>
                                {t("features.my-establishments.capacities.deleteCapacity")}
                            </DrawerTitle>
                            <DrawerDescription>
                                {t("features.my-establishments.capacities.deleteConfirmation")}
                            </DrawerDescription>
                        </DrawerHeader>
                        <DrawerFooter>
                            <Button variant="destructive" onClick={handleDelete}>
                                {t("common.actions.delete")}
                            </Button>
                            <DrawerClose asChild>
                                <Button variant="outline">{t("common.actions.cancel")}</Button>
                            </DrawerClose>
                        </DrawerFooter>
                    </DrawerContent>
                </Drawer>
            ) : (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="absolute end-3 top-3 z-20 size-8 text-muted-foreground opacity-0 transition-opacity hover:text-destructive group-hover:opacity-100"
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {t("features.my-establishments.capacities.deleteCapacity")}
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                {t("features.my-establishments.capacities.deleteConfirmation")}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>{t("common.actions.cancel")}</AlertDialogCancel>
                            <AlertDialogAction variant="destructive" onClick={handleDelete}>
                                {t("common.actions.delete")}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}

            <div className="relative group flex h-40 items-center justify-center">
                <BackgroundShapeSvg
                    className={cn(
                        "absolute top-4 left-1/2 -translate-x-1/2 -translate-y-1/2 text-muted scale-125",
                        "z-0 transition-all duration-300 group-hover:scale-110",
                    )}
                    style={{ transform: `rotate(${rotation}deg)` }}
                />
                {isIllustratedType(normalizedCode) ? (
                    <div className="relative z-10 h-28 w-28">
                        <Image
                            src={`/illustrations/pets/${normalizedCode}.svg`}
                            alt={capacity.animalType.name}
                            className="object-contain"
                            fill
                        />
                    </div>
                ) : (
                    <PawPrint className="relative z-10 size-12 text-primary" />
                )}
            </div>

            <div className="flex flex-col gap-3 p-4">
                <div className="flex items-center justify-between gap-2">
                    <h3 className="text-lg font-semibold">{capacity.animalType.name}</h3>
                    <span className="text-sm font-medium tabular-nums text-muted-foreground">
                        {t("features.my-establishments.capacities.occupancy", {
                            occupied: capacity.occupiedSpots,
                            max: maxCapacity,
                        })}
                    </span>
                </div>

                <div className="flex flex-col gap-1">
                    <NumberStepper
                        value={maxCapacity}
                        onIncrement={() => updateCapacityValue(maxCapacity + 1)}
                        onDecrement={() => updateCapacityValue(maxCapacity - 1)}
                        min={1}
                        max={MAX_CAPACITY}
                    />
                    <NumberStepper
                        value={pricePerNight}
                        onIncrement={() => updatePriceValue(pricePerNight + 1)}
                        onDecrement={() => updatePriceValue(pricePerNight - 1)}
                        min={0}
                        max={MAX_PRICE}
                        formatValue={(price) =>
                            t("features.my-establishments.capacities.priceValue", { price })
                        }
                    />
                </div>
            </div>
        </div>
    );
}
