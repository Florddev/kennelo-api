"use client";

import { useTranslations } from "next-intl";
import { Moon, PawPrint, Plus, Trash2 } from "lucide-react";

import { weekdayMaskContains } from "@workspace/common";
import type { AnimalTypeModel } from "@workspace/modules/pets";
import { Button } from "@workspace/ui/components/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@workspace/ui/components/dropdown-menu";
import { Input } from "@workspace/ui/components/input";
import { cn } from "@workspace/ui/lib/utils";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import {
    WEEKDAY_LABEL_KEYS,
    WEEKDAY_ORDER,
    type CycleMatrixRow,
    type CycleMatrixValue,
} from "../../lib/cycle-helpers";

const DEFAULT_CAPACITY = 10;
const DEFAULT_PRICE = 20;

const CLOSED_STRIPES = {
    backgroundImage:
        "repeating-linear-gradient(135deg, var(--color-muted) 0, var(--color-muted) 1px, transparent 1px, transparent 7px)",
};

type CycleMatrixEditorProps = {
    animalTypes: AnimalTypeModel[];
    value: CycleMatrixValue;
    onChange: (value: CycleMatrixValue) => void;
    lockSpecies?: boolean;
};

function SpeciesIcon({ code, name }: { code: string; name: string }) {
    if (isIllustratedType(code.toLowerCase())) {
        return <PetTypeIllustration code={code} name={name} className="size-6" />;
    }

    return <PawPrint className="size-5 text-muted-foreground" />;
}

export function CycleMatrixEditor({
    animalTypes,
    value,
    onChange,
    lockSpecies = false,
}: CycleMatrixEditorProps) {
    const t = useTranslations();

    const usedIds = value.rows.map((row) => row.animalTypeId);
    const availableTypes = animalTypes.filter((type) => !usedIds.includes(type.id));

    const animalType = (animalTypeId: string) =>
        animalTypes.find((type) => type.id === animalTypeId);

    const baseFor = (row: CycleMatrixRow): number => {
        const openPrices = WEEKDAY_ORDER.filter((weekday) =>
            weekdayMaskContains(value.openMask, weekday),
        ).map((weekday) => row.prices[weekday] ?? DEFAULT_PRICE);

        return openPrices.length > 0 ? Math.min(...openPrices) : 0;
    };

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

    const addType = (animalTypeId: string) => {
        onChange({
            ...value,
            rows: [...value.rows, { animalTypeId, maxCapacity: DEFAULT_CAPACITY, prices: {} }],
        });
    };

    const removeRow = (index: number) => {
        onChange({ ...value, rows: value.rows.filter((_, position) => position !== index) });
    };

    return (
        <div data-slot="cycle-matrix-editor" className="flex flex-col gap-4">
            <div className="overflow-x-auto rounded-2xl border">
                <table className="w-full min-w-[680px] table-fixed border-collapse text-sm">
                    <colgroup>
                        <col style={{ width: "22%" }} />
                        <col style={{ width: "11%" }} />
                        {WEEKDAY_ORDER.map((weekday) => (
                            <col key={weekday} />
                        ))}
                    </colgroup>
                    <thead>
                        <tr className="border-b bg-muted/30">
                            <th className="p-3 text-start align-bottom">
                                <span className="text-xs font-medium uppercase text-muted-foreground">
                                    {t("features.activities.cycles.matrix.species")}
                                </span>
                            </th>
                            <th className="border-s p-3 text-center align-bottom">
                                <div className="flex flex-col items-center gap-0.5">
                                    <span className="text-sm font-semibold">
                                        {t("features.activities.cycles.matrix.capacity")}
                                    </span>
                                    <span className="text-[10px] uppercase text-muted-foreground">
                                        {t("features.activities.cycles.matrix.wholeCycle")}
                                    </span>
                                </div>
                            </th>
                            {WEEKDAY_ORDER.map((weekday, index) => {
                                const isOpen = weekdayMaskContains(value.openMask, weekday);
                                return (
                                    <th
                                        key={weekday}
                                        className={cn(
                                            "p-2 text-center align-bottom",
                                            index === 0 && "border-s",
                                        )}
                                    >
                                        <div className="flex flex-col items-center gap-1.5">
                                            <span
                                                className={cn(
                                                    "whitespace-nowrap text-xs font-semibold",
                                                    !isOpen && "text-muted-foreground",
                                                )}
                                            >
                                                {t(
                                                    `features.activities.cycles.weekdaysLong.${WEEKDAY_LABEL_KEYS[weekday]}`,
                                                )}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => toggleWeekday(weekday)}
                                                aria-pressed={isOpen}
                                                className={cn(
                                                    "inline-flex items-center gap-1.5 whitespace-nowrap rounded-4xl px-2 py-0.5 text-[11px] font-medium transition-colors",
                                                    isOpen
                                                        ? "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300"
                                                        : "bg-rose-500/15 text-rose-600 dark:text-rose-300",
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        "size-1.5 rounded-full",
                                                        isOpen ? "bg-emerald-500" : "bg-rose-500",
                                                    )}
                                                />
                                                {isOpen
                                                    ? t("features.activities.availabilities.open")
                                                    : t(
                                                          "features.activities.availabilities.closed",
                                                      )}
                                            </button>
                                        </div>
                                    </th>
                                );
                            })}
                        </tr>
                    </thead>
                    <tbody>
                        {value.rows.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={WEEKDAY_ORDER.length + 2}
                                    className="p-6 text-center text-sm text-muted-foreground"
                                >
                                    {t("features.activities.cycles.matrix.empty")}
                                </td>
                            </tr>
                        ) : (
                            value.rows.map((row, index) => {
                                const type = animalType(row.animalTypeId);
                                return (
                                    <tr key={row.animalTypeId} className="border-b last:border-b-0">
                                        <td className="p-3">
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-muted">
                                                    <SpeciesIcon
                                                        code={type?.code ?? ""}
                                                        name={type?.name ?? ""}
                                                    />
                                                </div>
                                                <div className="flex min-w-0 flex-col">
                                                    <span className="truncate text-sm font-semibold">
                                                        {type?.name ?? row.animalTypeId}
                                                    </span>
                                                    <span className="text-[11px] text-muted-foreground">
                                                        {t(
                                                            "features.activities.cycles.matrix.baseSummary",
                                                            {
                                                                price: baseFor(row),
                                                                count: row.maxCapacity,
                                                            },
                                                        )}
                                                    </span>
                                                </div>
                                                {lockSpecies ? null : (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="ms-auto size-8 shrink-0 text-muted-foreground hover:text-destructive"
                                                        onClick={() => removeRow(index)}
                                                        aria-label={t("common.actions.delete")}
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                        <td className="border-s p-2">
                                            <Input
                                                type="number"
                                                min={1}
                                                value={row.maxCapacity}
                                                onChange={(event) =>
                                                    updateRow(index, {
                                                        maxCapacity: Number(event.target.value),
                                                    })
                                                }
                                                className="h-9 text-center"
                                                aria-label={t(
                                                    "features.activities.cycles.matrix.capacity",
                                                )}
                                            />
                                        </td>
                                        {WEEKDAY_ORDER.map((weekday, dayIndex) => {
                                            const isOpen = weekdayMaskContains(
                                                value.openMask,
                                                weekday,
                                            );

                                            if (!isOpen) {
                                                return (
                                                    <td
                                                        key={weekday}
                                                        style={CLOSED_STRIPES}
                                                        className={cn(
                                                            "p-2",
                                                            dayIndex === 0 && "border-s",
                                                        )}
                                                    >
                                                        <div className="flex flex-col items-center justify-center gap-0.5 py-1 text-muted-foreground">
                                                            <Moon className="size-3.5" />
                                                            <span className="text-[10px]">
                                                                {t(
                                                                    "features.activities.availabilities.closed",
                                                                )}
                                                            </span>
                                                        </div>
                                                    </td>
                                                );
                                            }

                                            return (
                                                <td
                                                    key={weekday}
                                                    className={cn(
                                                        "p-2",
                                                        dayIndex === 0 && "border-s",
                                                    )}
                                                >
                                                    <div className="relative">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            step="0.5"
                                                            value={
                                                                row.prices[weekday] ?? DEFAULT_PRICE
                                                            }
                                                            onChange={(event) =>
                                                                updatePrice(
                                                                    index,
                                                                    weekday,
                                                                    Number(event.target.value),
                                                                )
                                                            }
                                                            className="h-9 pe-5 text-center"
                                                        />
                                                        <span className="pointer-events-none absolute inset-y-0 end-2 flex items-center text-xs text-muted-foreground">
                                                            {t(
                                                                "features.activities.cycles.matrix.currency",
                                                            )}
                                                        </span>
                                                    </div>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>

            {lockSpecies ? null : (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            className="gap-1.5 self-start rounded-4xl"
                            disabled={availableTypes.length === 0}
                        >
                            <Plus className="size-4" />
                            {t("features.activities.cycles.matrix.addSpecies")}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        {availableTypes.map((type) => (
                            <DropdownMenuItem
                                key={type.id}
                                className="gap-2"
                                onSelect={() => addType(type.id)}
                            >
                                <SpeciesIcon code={type.code} name={type.name} />
                                {type.name}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    );
}
