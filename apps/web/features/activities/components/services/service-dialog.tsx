"use client";

import { useEffect } from "react";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Trash2 } from "lucide-react";

import {
    createService,
    updateService,
    deleteService,
    createServiceSchema,
    type CreateServiceInput,
    type ServiceModel,
} from "@workspace/modules/services";
import { getAnimalTypes } from "@workspace/modules/pets";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";
import { Label } from "@workspace/ui/components/label";
import { Textarea } from "@workspace/ui/components/textarea";
import { Switch } from "@workspace/ui/components/switch";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityServicesQueryKey } from "../../hooks/use-activity-services";

type ServiceDialogProps = {
    activityId: string;
    service: ServiceModel | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function ServiceDialog({ activityId, service, open, onOpenChange }: ServiceDialogProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading, error } = useAsyncState();
    const remove = useAsyncState();

    const { control, register, handleSubmit, reset } = useForm<CreateServiceInput>({
        resolver: zodResolver(createServiceSchema),
        defaultValues: { name: "", animalTypeId: "", price: 0, description: "", isIncluded: true },
    });

    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    useEffect(() => {
        if (open) {
            reset({
                name: service?.name ?? "",
                animalTypeId: service?.animalTypeId ?? "",
                price: service ? Number(service.price ?? 0) : 0,
                description: service?.description ?? "",
                isIncluded: service?.isIncluded ?? true,
            });
        }
    }, [open, service, reset]);

    const invalidate = () =>
        queryClient.invalidateQueries({ queryKey: activityServicesQueryKey(activityId) });

    const onSubmit = (data: CreateServiceInput) =>
        execute(
            () =>
                service
                    ? updateService(activityId, service.id, data)
                    : createService(activityId, data),
            {
                displayError: true,
                onSuccess: () => {
                    invalidate();
                    toast.success(t("features.activities.services.saved"));
                    onOpenChange(false);
                },
            },
        );

    const handleDelete = () => {
        if (!service) return;
        remove.execute(() => deleteService(activityId, service.id), {
            displayError: true,
            onSuccess: () => {
                invalidate();
                toast.success(t("features.activities.services.deleted"));
                onOpenChange(false);
            },
        });
    };

    const title = service
        ? t("features.activities.services.editTitle")
        : t("features.activities.services.createTitle");

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="service-dialog">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="service-name">
                            {t("features.activities.services.name")}
                        </Label>
                        <Input id="service-name" {...register("name")} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label>{t("features.activities.services.animalType")}</Label>
                        <Controller
                            control={control}
                            name="animalTypeId"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue
                                            placeholder={t(
                                                "features.activities.services.selectAnimalType",
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {animalTypes.map((type) => (
                                            <SelectItem key={type.id} value={type.id}>
                                                {type.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="service-price">
                            {t("features.activities.services.price")}
                        </Label>
                        <Input
                            id="service-price"
                            type="number"
                            min={0}
                            step="0.01"
                            {...register("price")}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="service-description">
                            {t("features.activities.services.description")}
                        </Label>
                        <Textarea id="service-description" rows={3} {...register("description")} />
                    </div>

                    <Controller
                        control={control}
                        name="isIncluded"
                        render={({ field }) => (
                            <div className="flex items-center justify-between">
                                <Label htmlFor="service-included">
                                    {t("features.activities.services.included")}
                                </Label>
                                <Switch
                                    id="service-included"
                                    checked={field.value}
                                    onCheckedChange={field.onChange}
                                />
                            </div>
                        )}
                    />

                    {error && (
                        <Alert variant="destructive">
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    <DialogFooter className="flex-row items-center justify-between gap-2 sm:justify-between">
                        {service ? (
                            <Button
                                type="button"
                                variant="ghost"
                                className="gap-1.5 text-destructive hover:text-destructive"
                                onClick={handleDelete}
                                disabled={remove.isLoading}
                            >
                                <Trash2 className="size-4" />
                                {t("common.actions.delete")}
                            </Button>
                        ) : (
                            <span />
                        )}
                        <Button type="submit" disabled={isLoading}>
                            {t("common.actions.save")}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
