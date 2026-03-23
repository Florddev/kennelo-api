"use client";

import { useEffect, useReducer, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { Plus, Trash2, PawPrint } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@workspace/ui/components/table";
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
import { Progress } from "@workspace/ui/components/progress";
import { KHeart } from "@workspace/ui/components/icons";
import {
    getCapacities,
    createCapacity,
    deleteCapacity,
    CapacityModel,
    createCapacitySchema,
    type CreateCapacityInput,
} from "@workspace/modules/establishments";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";

export function CapacitiesTab({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const [capacities, setCapacities] = useState<CapacityModel[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [refreshKey, refresh] = useReducer((x: number) => x + 1, 0);
    const { execute } = useAsyncState();

    useEffect(() => {
        let active = true;
        getCapacities(establishmentId).then(
            (data) => {
                if (active) {
                    setCapacities(data);
                    setIsLoading(false);
                }
            },
            () => {
                if (active) {
                    setCapacities([]);
                    setIsLoading(false);
                }
            },
        );
        return () => {
            active = false;
        };
    }, [establishmentId, refreshKey]);

    const handleDelete = async (capacityId: number) => {
        await execute(() => deleteCapacity(establishmentId, capacityId));
        refresh();
    };

    if (isLoading) {
        return null;
    }

    const totalCapacity = capacities.reduce((sum, c) => sum + c.maxCapacity, 0);
    const totalOccupied = capacities.reduce((sum, c) => sum + c.occupiedSpots, 0);

    return (
        <div className="space-y-6">
            {capacities.length > 0 && (
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-start gap-4 pt-6">
                            <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                                <KHeart size={20} secondary="text-primary" secondaryOpacity={0.6} />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    {t("features.my-establishments.capacities.title")}
                                </p>
                                <p className="text-2xl font-bold tabular-nums mt-0.5">
                                    {capacities.length}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-start gap-4 pt-6">
                            <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                                <PawPrint className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    {t("features.my-establishments.dashboard.totalCapacity")}
                                </p>
                                <p className="text-2xl font-bold tabular-nums mt-0.5">
                                    {totalCapacity}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-start gap-4 pt-6">
                            <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                                <PawPrint className="size-5 text-primary" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    {t("features.my-establishments.dashboard.occupiedSpots")}
                                </p>
                                <p className="text-2xl font-bold tabular-nums mt-0.5">
                                    {totalOccupied} / {totalCapacity}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                            {t("features.my-establishments.capacities.title")}
                        </CardTitle>
                        <Button
                            size="sm"
                            className="rounded-4xl gap-1.5"
                            onClick={() => setShowForm(!showForm)}
                        >
                            <Plus className="size-4" />
                            {t("features.my-establishments.capacities.addCapacity")}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    {showForm && (
                        <div className="mb-6 rounded-2xl border bg-muted/30 p-4">
                            <CreateCapacityForm
                                establishmentId={establishmentId}
                                onCreated={() => {
                                    setShowForm(false);
                                    refresh();
                                }}
                                onCancel={() => setShowForm(false)}
                            />
                        </div>
                    )}

                    {capacities.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <div className="flex items-center justify-center size-12 rounded-full bg-muted mb-4">
                                <PawPrint className="size-6 text-muted-foreground" />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {t("features.my-establishments.capacities.empty")}
                            </p>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t("common.fields.animalType")}</TableHead>
                                    <TableHead className="text-end">
                                        {t("common.fields.maxCapacity")}
                                    </TableHead>
                                    <TableHead className="text-end">
                                        {t("common.fields.pricePerNight")}
                                    </TableHead>
                                    <TableHead className="w-[25%]">
                                        {t("features.my-establishments.dashboard.occupancyRate")}
                                    </TableHead>
                                    <TableHead className="w-10" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {capacities.map((capacity) => {
                                    const rate =
                                        capacity.maxCapacity > 0
                                            ? Math.round(
                                                  (capacity.occupiedSpots / capacity.maxCapacity) *
                                                      100,
                                              )
                                            : 0;
                                    return (
                                        <TableRow key={capacity.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex items-center justify-center size-8 rounded-lg bg-primary/10 shrink-0">
                                                        <PawPrint className="size-4 text-primary" />
                                                    </div>
                                                    <span className="font-medium">
                                                        {capacity.animalType.name}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-end">
                                                <Badge variant="secondary" className="tabular-nums">
                                                    {t(
                                                        "features.my-establishments.capacities.spots",
                                                        {
                                                            count: capacity.maxCapacity,
                                                        },
                                                    )}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-end tabular-nums font-medium">
                                                {t(
                                                    "features.my-establishments.capacities.perNight",
                                                    {
                                                        price: capacity.pricePerNight.toFixed(2),
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <Progress
                                                        value={Math.min(rate, 100)}
                                                        className="h-2"
                                                    />
                                                    <span className="text-xs tabular-nums text-muted-foreground min-w-[3.5rem] text-end">
                                                        {capacity.occupiedSpots}/
                                                        {capacity.maxCapacity}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-muted-foreground hover:text-destructive"
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>
                                                                {t(
                                                                    "features.my-establishments.capacities.deleteCapacity",
                                                                )}
                                                            </AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                {t(
                                                                    "features.my-establishments.capacities.deleteConfirmation",
                                                                )}
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>
                                                                {t("common.actions.cancel")}
                                                            </AlertDialogCancel>
                                                            <AlertDialogAction
                                                                variant="destructive"
                                                                onClick={() =>
                                                                    handleDelete(capacity.id)
                                                                }
                                                            >
                                                                {t("common.actions.delete")}
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

function CreateCapacityForm({
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

    const { control, handleSubmit, setError } = useForm<CreateCapacityInput>({
        resolver: zodResolver(createCapacitySchema),
        defaultValues: {
            animalTypeId: 0,
            maxCapacity: 1,
            pricePerNight: 0,
        },
    });

    const onSubmit = async (data: CreateCapacityInput) => {
        const result = await execute(() => createCapacity(establishmentId, data), {
            setFieldError: setError,
        });
        if (result) {
            onCreated();
        }
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <InputController
                name="animalTypeId"
                control={control}
                label={t("common.fields.animalType")}
                placeholder={t("common.placeholders.selectAnimalType")}
                type="number"
            />
            <div className="grid grid-cols-2 gap-4">
                <InputController
                    name="maxCapacity"
                    control={control}
                    label={t("common.fields.maxCapacity")}
                    placeholder={t("common.placeholders.maxCapacity")}
                    type="number"
                />
                <InputController
                    name="pricePerNight"
                    control={control}
                    label={t("common.fields.pricePerNight")}
                    placeholder={t("common.placeholders.pricePerNight")}
                    type="number"
                />
            </div>
            <div className="flex justify-end gap-3 pt-2">
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-4xl"
                    onClick={onCancel}
                    disabled={isLoading}
                >
                    {t("common.actions.cancel")}
                </Button>
                <Button type="submit" className="rounded-4xl" disabled={isLoading}>
                    {isLoading ? t("common.actions.loading") : t("common.actions.create")}
                </Button>
            </div>
        </form>
    );
}
