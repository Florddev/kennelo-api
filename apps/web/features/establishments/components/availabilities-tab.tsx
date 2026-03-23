"use client";

import { useEffect, useReducer, useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useLocale, useTranslations } from "next-intl";
import { Plus, Trash2, ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { Separator } from "@workspace/ui/components/separator";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";
import { Field, FieldLabel, FieldError } from "@workspace/ui/components/field";
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
import { KCalendar } from "@workspace/ui/components/icons";
import {
    getAvailabilities,
    createAvailability,
    deleteAvailability,
    AvailabilityModel,
    createAvailabilitySchema,
    type CreateAvailabilityInput,
} from "@workspace/modules/establishments";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";

function formatMonth(year: number, month: number): string {
    return `${year}-${String(month).padStart(2, "0")}`;
}

function getMonthLabel(year: number, month: number, locale: string): string {
    const date = new Date(year, month - 1, 1);
    return date.toLocaleDateString(locale, { month: "long", year: "numeric" });
}

function getDayNames(locale: string): string[] {
    const baseDate = new Date(2024, 0, 1);
    return Array.from({ length: 7 }).map((_, i) => {
        const date = new Date(baseDate);
        date.setDate(baseDate.getDate() + i);
        return date.toLocaleDateString(locale, { weekday: "short" });
    });
}

function getDaysInMonth(year: number, month: number): number {
    return new Date(year, month, 0).getDate();
}

function getFirstDayOfWeek(year: number, month: number): number {
    const day = new Date(year, month - 1, 1).getDay();
    return day === 0 ? 6 : day - 1;
}

const T_OPEN = "features.my-establishments.availabilities.open" as const;
const T_CLOSED = "features.my-establishments.availabilities.closed" as const;

function CalendarDayCell({
    day,
    dateStr,
    availability,
    isToday,
    onDelete,
}: {
    day: number;
    dateStr: string;
    availability: AvailabilityModel | undefined;
    isToday: boolean;
    onDelete: (id: number) => void;
}) {
    const t = useTranslations();
    const isClosed = availability?.status === "closed";

    let statusClass = "text-muted-foreground hover:bg-muted/50";
    if (isClosed) statusClass = "bg-destructive/10 text-destructive";
    else if (availability) statusClass = "bg-primary/10 text-primary font-medium";
    const todayClass = isToday ? "ring-2 ring-primary ring-offset-2 ring-offset-background" : "";

    return (
        <div
            className={`relative flex flex-col items-center justify-center rounded-xl p-2.5 text-sm transition-colors ${statusClass} ${todayClass}`}
        >
            <span className="tabular-nums">{day}</span>
            {availability && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <button className="absolute inset-0 rounded-xl hover:bg-foreground/5 transition-colors" />
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>{dateStr}</AlertDialogTitle>
                            <AlertDialogDescription asChild>
                                <div className="flex flex-col gap-3">
                                    <Badge
                                        variant={isClosed ? "destructive" : "default"}
                                        className="w-fit"
                                    >
                                        {isClosed ? t(T_CLOSED) : t(T_OPEN)}
                                    </Badge>
                                    {availability.note && (
                                        <p className="text-muted-foreground text-sm">
                                            {availability.note}
                                        </p>
                                    )}
                                </div>
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>{t("common.actions.close")}</AlertDialogCancel>
                            <AlertDialogAction
                                variant="destructive"
                                onClick={() => onDelete(availability.id)}
                            >
                                <Trash2 className="size-4 me-1.5" />
                                {t("common.actions.delete")}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}
        </div>
    );
}

export function AvailabilitiesTab({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const locale = useLocale();
    const now = new Date();
    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [availabilities, setAvailabilities] = useState<AvailabilityModel[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [refreshKey, refresh] = useReducer((x: number) => x + 1, 0);
    const { execute } = useAsyncState();

    useEffect(() => {
        let active = true;
        getAvailabilities(establishmentId, formatMonth(year, month)).then(
            (data) => {
                if (active) {
                    setAvailabilities(data);
                    setIsLoading(false);
                }
            },
            () => {
                if (active) {
                    setAvailabilities([]);
                    setIsLoading(false);
                }
            },
        );
        return () => {
            active = false;
        };
    }, [establishmentId, year, month, refreshKey]);

    const handlePrevMonth = () => {
        if (month === 1) {
            setYear(year - 1);
            setMonth(12);
        } else {
            setMonth(month - 1);
        }
    };

    const handleNextMonth = () => {
        if (month === 12) {
            setYear(year + 1);
            setMonth(1);
        } else {
            setMonth(month + 1);
        }
    };

    const handleDelete = async (availabilityId: number) => {
        await execute(() => deleteAvailability(establishmentId, availabilityId));
        refresh();
    };

    const availabilityMap = new Map(availabilities.map((a) => [a.date, a]));

    const daysInMonth = getDaysInMonth(year, month);
    const firstDay = getFirstDayOfWeek(year, month);

    const openCount = availabilities.filter((a) => a.status === "open").length;
    const closedCount = availabilities.filter((a) => a.status === "closed").length;

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                            <KCalendar size={20} secondary="text-primary" secondaryOpacity={0.6} />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.availabilities.title")}
                            </p>
                            <p className="text-2xl font-bold tabular-nums mt-0.5">
                                {availabilities.length}
                            </p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                            <KCalendar
                                size={20}
                                filled
                                secondary="text-primary"
                                secondaryOpacity={0.6}
                            />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t(T_OPEN)}
                            </p>
                            <p className="text-2xl font-bold tabular-nums mt-0.5">{openCount}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-destructive/10 shrink-0">
                            <KCalendar
                                size={20}
                                secondary="text-destructive"
                                secondaryOpacity={0.6}
                            />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t(T_CLOSED)}
                            </p>
                            <p className="text-2xl font-bold tabular-nums mt-0.5">{closedCount}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-5">
                <Card className="lg:col-span-3">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                onClick={handlePrevMonth}
                            >
                                <ChevronLeft className="size-4" />
                            </Button>
                            <CardTitle className="text-base capitalize">
                                {getMonthLabel(year, month, locale)}
                            </CardTitle>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                onClick={handleNextMonth}
                            >
                                <ChevronRight className="size-4" />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {isLoading ? null : (
                            <div className="grid grid-cols-7 gap-1">
                                {getDayNames(locale).map((day, i) => (
                                    <div
                                        key={i}
                                        className="text-center text-xs font-medium text-muted-foreground py-2 uppercase"
                                    >
                                        {day}
                                    </div>
                                ))}
                                {Array.from({ length: firstDay }).map((_, i) => (
                                    <div key={`empty-${i}`} />
                                ))}
                                {Array.from({ length: daysInMonth }).map((_, i) => {
                                    const day = i + 1;
                                    const dateStr = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                                    const isToday =
                                        day === now.getDate() &&
                                        month === now.getMonth() + 1 &&
                                        year === now.getFullYear();

                                    return (
                                        <CalendarDayCell
                                            key={day}
                                            day={day}
                                            dateStr={dateStr}
                                            availability={availabilityMap.get(dateStr)}
                                            isToday={isToday}
                                            onDelete={handleDelete}
                                        />
                                    );
                                })}
                            </div>
                        )}
                        <Separator className="my-4" />
                        <div className="flex items-center gap-6 text-xs text-muted-foreground">
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full bg-primary/30" />
                                <span>{t(T_OPEN)}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full bg-destructive/30" />
                                <span>{t(T_CLOSED)}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="size-3 rounded-full ring-2 ring-primary" />
                                <span>{t("features.my-establishments.dashboard.todayStatus")}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.availabilities.addAvailability")}
                            </CardTitle>
                            {!showForm && (
                                <Button
                                    size="sm"
                                    className="rounded-4xl gap-1.5"
                                    onClick={() => setShowForm(true)}
                                >
                                    <Plus className="size-4" />
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {showForm ? (
                            <CreateAvailabilityForm
                                establishmentId={establishmentId}
                                onCreated={() => {
                                    setShowForm(false);
                                    refresh();
                                }}
                                onCancel={() => setShowForm(false)}
                            />
                        ) : (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <div className="flex items-center justify-center size-12 rounded-full bg-muted mb-3">
                                    <KCalendar
                                        size={24}
                                        secondary="text-muted-foreground"
                                        secondaryOpacity={0.5}
                                    />
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {t("features.my-establishments.availabilities.empty")}
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-4xl gap-1.5 mt-4"
                                    onClick={() => setShowForm(true)}
                                >
                                    <Plus className="size-4" />
                                    {t("features.my-establishments.availabilities.addAvailability")}
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function CreateAvailabilityForm({
    establishmentId,
    onCreated,
    onCancel,
}: {
    establishmentId: string;
    onCreated: () => void;
    onCancel: () => void;
}) {
    const t = useTranslations();
    const { execute, isLoading } = useAsyncState();

    const { control, handleSubmit, setError } = useForm<CreateAvailabilityInput>({
        resolver: zodResolver(createAvailabilitySchema),
        defaultValues: {
            startDate: "",
            endDate: "",
            status: "open",
            note: "",
        },
    });

    const onSubmit = async (data: CreateAvailabilityInput) => {
        const result = await execute(() => createAvailability(establishmentId, data), {
            setFieldError: setError,
        });
        if (result) {
            onCreated();
        }
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <InputController
                name="startDate"
                control={control}
                label={t("common.fields.startDate")}
                type="date"
            />
            <InputController
                name="endDate"
                control={control}
                label={t("common.fields.endDate")}
                type="date"
            />
            <Controller
                name="status"
                control={control}
                render={({ field, fieldState }) => (
                    <Field data-invalid={fieldState.invalid} className="gap-1.5 group">
                        <FieldLabel htmlFor="status">{t("common.fields.status")}</FieldLabel>
                        <Select value={field.value} onValueChange={field.onChange}>
                            <SelectTrigger id="status" className="rounded-2xl bg-card py-6">
                                <SelectValue placeholder={t("common.placeholders.selectStatus")} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="open">{t(T_OPEN)}</SelectItem>
                                <SelectItem value="closed">{t(T_CLOSED)}</SelectItem>
                            </SelectContent>
                        </Select>
                        {fieldState.invalid && <FieldError errors={[fieldState.error]} />}
                    </Field>
                )}
            />
            <InputController
                name="note"
                control={control}
                label={t("common.fields.note")}
                placeholder={t("common.placeholders.note")}
            />
            <div className="flex gap-3 pt-2">
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-4xl flex-1"
                    onClick={onCancel}
                    disabled={isLoading}
                >
                    {t("common.actions.cancel")}
                </Button>
                <Button type="submit" className="rounded-4xl flex-1" disabled={isLoading}>
                    {isLoading ? t("common.actions.loading") : t("common.actions.create")}
                </Button>
            </div>
        </form>
    );
}
