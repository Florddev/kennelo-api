"use client";

import { type ReactNode, useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Plus, Trash2 } from "lucide-react";

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
import { ALL_WEEKDAYS } from "@workspace/common";
import { upsertCycleSettings, type ActivityCycleModel } from "@workspace/modules/activities";
import { getAnimalTypes } from "@workspace/modules/pets";

import { useAsyncState } from "@/hooks/use-async-state";
import { useIsMobile } from "@/hooks/use-mobile";
import { activityCyclesQueryKey } from "../hooks/use-activity-cycles";
import { WeekdaySelector } from "./weekday-selector";

const DEFAULT_CAPACITY = 10;
const DEFAULT_PRICE = 20;
const MAX_CAPACITY = 999;
const MAX_PRICE = 9999;

type DraftSetting = {
    animalTypeId: string;
    maxCapacity: number;
    price: number;
    sumWeekdays: number;
};

function toDrafts(cycle: ActivityCycleModel): DraftSetting[] {
    return cycle.settings.map((setting) => ({
        animalTypeId: setting.animalType.id,
        maxCapacity: setting.maxCapacity,
        price: setting.price,
        sumWeekdays: setting.sumWeekdays,
    }));
}

type CycleSettingsEditorProps = {
    activityId: string;
    cycle: ActivityCycleModel;
    trigger: ReactNode;
};

export function CycleSettingsEditor({ activityId, cycle, trigger }: CycleSettingsEditorProps) {
    const t = useTranslations();
    const isMobile = useIsMobile();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();

    const [open, setOpen] = useState(false);
    const [drafts, setDrafts] = useState<DraftSetting[]>(() => toDrafts(cycle));

    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    const usedIds = drafts.map((draft) => draft.animalTypeId);
    const availableTypes = animalTypes.filter((type) => !usedIds.includes(type.id));

    const handleOpenChange = (next: boolean) => {
        if (next) {
            setDrafts(toDrafts(cycle));
        }
        setOpen(next);
    };

    const addRow = () => {
        const next = availableTypes[0];
        if (!next) return;
        setDrafts((current) => [
            ...current,
            {
                animalTypeId: next.id,
                maxCapacity: DEFAULT_CAPACITY,
                price: DEFAULT_PRICE,
                sumWeekdays: ALL_WEEKDAYS,
            },
        ]);
    };

    const updateRow = (index: number, patch: Partial<DraftSetting>) => {
        setDrafts((current) =>
            current.map((draft, position) => (position === index ? { ...draft, ...patch } : draft)),
        );
    };

    const removeRow = (index: number) => {
        setDrafts((current) => current.filter((_, position) => position !== index));
    };

    const handleSave = async () => {
        const result = await execute(
            () => upsertCycleSettings(activityId, cycle.id, { settings: drafts }),
            {
                onSuccess: () => {
                    queryClient.invalidateQueries({
                        queryKey: activityCyclesQueryKey(activityId),
                    });
                },
            },
        );
        if (result) {
            setOpen(false);
        }
    };

    const animalTypeName = (animalTypeId: string) =>
        animalTypes.find((type) => type.id === animalTypeId)?.name ?? animalTypeId;

    const title = t("features.activities.cycles.editSettings");

    const body = (
        <div className="flex flex-col gap-4">
            {drafts.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t("features.activities.cycles.settingsEmpty")}
                </p>
            ) : (
                drafts.map((draft, index) => (
                    <div
                        key={draft.animalTypeId}
                        className="flex flex-col gap-3 rounded-2xl border p-3"
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-sm font-semibold">
                                {animalTypeName(draft.animalTypeId)}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8 text-muted-foreground hover:text-destructive"
                                onClick={() => removeRow(index)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                        <NumberStepper
                            label={t("common.fields.maxCapacity")}
                            value={draft.maxCapacity}
                            onIncrement={() =>
                                updateRow(index, { maxCapacity: draft.maxCapacity + 1 })
                            }
                            onDecrement={() =>
                                updateRow(index, { maxCapacity: draft.maxCapacity - 1 })
                            }
                            min={1}
                            max={MAX_CAPACITY}
                        />
                        <NumberStepper
                            label={t("common.fields.pricePerNight")}
                            value={draft.price}
                            onIncrement={() => updateRow(index, { price: draft.price + 1 })}
                            onDecrement={() => updateRow(index, { price: draft.price - 1 })}
                            min={0}
                            max={MAX_PRICE}
                            formatValue={(price) =>
                                t("features.activities.cycles.priceValue", { price })
                            }
                        />
                        <div className="flex flex-col gap-1.5">
                            <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {t("features.activities.cycles.applicableDays")}
                            </span>
                            <WeekdaySelector
                                value={draft.sumWeekdays}
                                onChange={(mask) => updateRow(index, { sumWeekdays: mask })}
                            />
                        </div>
                    </div>
                ))
            )}

            <Button
                variant="outline"
                className="gap-1.5 rounded-4xl"
                onClick={addRow}
                disabled={availableTypes.length === 0}
            >
                <Plus className="size-4" />
                {t("features.activities.cycles.addSpecies")}
            </Button>
        </div>
    );

    const saveButton = (
        <Button className="rounded-4xl" onClick={handleSave} disabled={isLoading}>
            {isLoading ? t("common.actions.loading") : t("common.actions.save")}
        </Button>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={handleOpenChange}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader className="text-start">
                        <DrawerTitle>{title}</DrawerTitle>
                    </DrawerHeader>
                    <div className="max-h-[60vh] overflow-y-auto px-4">{body}</div>
                    <DrawerFooter>{saveButton}</DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>
                <div className="max-h-[60vh] overflow-y-auto">{body}</div>
                <DialogFooter>{saveButton}</DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
