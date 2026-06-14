"use client";

import { useTranslations } from "next-intl";
import { Plus, Trash2 } from "lucide-react";

import { weekdayMaskContains } from "@workspace/common";
import type { AnimalTypeModel } from "@workspace/modules/pets";
import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";
import { Switch } from "@workspace/ui/components/switch";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@workspace/ui/components/table";
import { cn } from "@workspace/ui/lib/utils";

import {
    WEEKDAY_LABEL_KEYS,
    WEEKDAY_ORDER,
    type CycleMatrixRow,
    type CycleMatrixValue,
} from "../../lib/cycle-helpers";

const DEFAULT_CAPACITY = 10;
const DEFAULT_PRICE = 20;

type CycleMatrixEditorProps = {
    animalTypes: AnimalTypeModel[];
    value: CycleMatrixValue;
    onChange: (value: CycleMatrixValue) => void;
};

export function CycleMatrixEditor({ animalTypes, value, onChange }: CycleMatrixEditorProps) {
    const t = useTranslations();

    const usedIds = value.rows.map((row) => row.animalTypeId);
    const availableTypes = animalTypes.filter((type) => !usedIds.includes(type.id));

    const animalTypeName = (animalTypeId: string) =>
        animalTypes.find((type) => type.id === animalTypeId)?.name ?? animalTypeId;

    const toggleWeekday = (weekday: number) => {
        const isOpen = weekdayMaskContains(value.openMask, weekday);
        onChange({
            ...value,
            openMask: isOpen ? value.openMask & ~weekday : value.openMask | weekday,
        });
    };

    const updateRow = (index: number, patch: Partial<CycleMatrixRow>) => {
        onChange({
            ...value,
            rows: value.rows.map((row, position) =>
                position === index ? { ...row, ...patch } : row,
            ),
        });
    };

    const updatePrice = (index: number, weekday: number, price: number) => {
        const row = value.rows[index];
        if (!row) return;
        updateRow(index, { prices: { ...row.prices, [weekday]: price } });
    };

    const addRow = () => {
        const next = availableTypes[0];
        if (!next) return;
        onChange({
            ...value,
            rows: [
                ...value.rows,
                { animalTypeId: next.id, maxCapacity: DEFAULT_CAPACITY, prices: {} },
            ],
        });
    };

    const removeRow = (index: number) => {
        onChange({ ...value, rows: value.rows.filter((_, position) => position !== index) });
    };

    return (
        <div data-slot="cycle-matrix-editor" className="flex flex-col gap-4">
            <div className="overflow-x-auto rounded-2xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="min-w-32">
                                {t("features.activities.cycles.matrix.species")}
                            </TableHead>
                            <TableHead className="text-center">
                                {t("features.activities.cycles.matrix.capacity")}
                            </TableHead>
                            {WEEKDAY_ORDER.map((weekday) => {
                                const isOpen = weekdayMaskContains(value.openMask, weekday);
                                return (
                                    <TableHead key={weekday} className="text-center">
                                        <div className="flex flex-col items-center gap-1">
                                            <span
                                                className={cn(
                                                    "text-xs font-medium uppercase",
                                                    !isOpen && "text-muted-foreground line-through",
                                                )}
                                            >
                                                {t(
                                                    `features.activities.cycles.weekdaysShort.${WEEKDAY_LABEL_KEYS[weekday]}`,
                                                )}
                                            </span>
                                            <Switch
                                                checked={isOpen}
                                                onCheckedChange={() => toggleWeekday(weekday)}
                                                aria-label={t(
                                                    `features.activities.cycles.weekdaysLong.${WEEKDAY_LABEL_KEYS[weekday]}`,
                                                )}
                                            />
                                        </div>
                                    </TableHead>
                                );
                            })}
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {value.rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={WEEKDAY_ORDER.length + 3}
                                    className="text-center text-sm text-muted-foreground"
                                >
                                    {t("features.activities.cycles.matrix.empty")}
                                </TableCell>
                            </TableRow>
                        ) : (
                            value.rows.map((row, index) => (
                                <TableRow key={row.animalTypeId}>
                                    <TableCell className="font-medium">
                                        {animalTypeName(row.animalTypeId)}
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            type="number"
                                            min={1}
                                            value={row.maxCapacity}
                                            onChange={(event) =>
                                                updateRow(index, {
                                                    maxCapacity: Number(event.target.value),
                                                })
                                            }
                                            className="mx-auto w-16 text-center"
                                        />
                                    </TableCell>
                                    {WEEKDAY_ORDER.map((weekday) => {
                                        const isOpen = weekdayMaskContains(value.openMask, weekday);
                                        return (
                                            <TableCell key={weekday}>
                                                {isOpen ? (
                                                    <Input
                                                        type="number"
                                                        min={0}
                                                        step="0.5"
                                                        value={row.prices[weekday] ?? DEFAULT_PRICE}
                                                        onChange={(event) =>
                                                            updatePrice(
                                                                index,
                                                                weekday,
                                                                Number(event.target.value),
                                                            )
                                                        }
                                                        className="mx-auto w-20 text-center"
                                                    />
                                                ) : (
                                                    <span className="block text-center text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                        );
                                    })}
                                    <TableCell>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-muted-foreground hover:text-destructive"
                                            onClick={() => removeRow(index)}
                                            aria-label={t("common.actions.delete")}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <Button
                variant="outline"
                className="gap-1.5 self-start rounded-4xl"
                onClick={addRow}
                disabled={availableTypes.length === 0}
            >
                <Plus className="size-4" />
                {t("features.activities.cycles.matrix.addSpecies")}
            </Button>
        </div>
    );
}
