"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { PawPrint, TrendingUp } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { Progress } from "@workspace/ui/components/progress";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@workspace/ui/components/table";
import { KHeart, KCalendar, KCompass, KHome } from "@workspace/ui/components/icons";
import { getEstablishmentDashboard, DashboardModel } from "@workspace/modules/establishments";

export function DashboardTab({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const [dashboard, setDashboard] = useState<DashboardModel | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let active = true;
        getEstablishmentDashboard(establishmentId).then(
            (data) => {
                if (active) {
                    setDashboard(data);
                    setIsLoading(false);
                }
            },
            () => {
                if (active) {
                    setDashboard(null);
                    setIsLoading(false);
                }
            },
        );
        return () => {
            active = false;
        };
    }, [establishmentId]);

    if (isLoading) {
        return null;
    }

    if (!dashboard) {
        return (
            <Card className="border-dashed">
                <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                    <div className="flex items-center justify-center size-12 rounded-full bg-muted mb-4">
                        <PawPrint className="size-6 text-muted-foreground" />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {t("features.my-establishments.dashboard.noCapacities")}
                    </p>
                </CardContent>
            </Card>
        );
    }

    const { summary, occupancyByAnimal } = dashboard;
    const overallRate =
        summary.totalCapacity > 0
            ? Math.round((summary.occupiedSpots / summary.totalCapacity) * 100)
            : 0;

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                            <KHome size={20} secondary="text-primary" secondaryOpacity={0.6} />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.dashboard.totalCapacity")}
                            </p>
                            <p className="text-2xl font-bold tabular-nums mt-0.5">
                                {summary.totalCapacity}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                            <KHeart size={20} secondary="text-primary" secondaryOpacity={0.6} />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.dashboard.occupiedSpots")}
                            </p>
                            <p className="text-2xl font-bold tabular-nums mt-0.5">
                                {summary.occupiedSpots}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                            <KCompass size={20} secondary="text-primary" secondaryOpacity={0.6} />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.dashboard.availableSpots")}
                            </p>
                            <p className="text-2xl font-bold tabular-nums mt-0.5">
                                {summary.availableSpots}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="flex items-start gap-4 pt-6">
                        <div className="flex items-center justify-center size-10 rounded-xl bg-primary/10 shrink-0">
                            <KCalendar size={20} secondary="text-primary" secondaryOpacity={0.6} />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.dashboard.todayStatus")}
                            </p>
                            <Badge
                                variant={summary.todayStatus === "open" ? "default" : "secondary"}
                                className="mt-1.5"
                            >
                                {summary.todayStatus === "open"
                                    ? t("features.my-establishments.availabilities.open")
                                    : t("features.my-establishments.availabilities.closed")}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-5">
                <Card className="lg:col-span-2">
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground uppercase tracking-wider">
                            <TrendingUp className="size-4" />
                            {t("features.my-establishments.dashboard.occupancyRate")}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col items-center justify-center py-6">
                        <div className="relative flex items-center justify-center size-32">
                            <svg className="size-full -rotate-90" viewBox="0 0 36 36">
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.9"
                                    fill="none"
                                    className="stroke-muted"
                                    strokeWidth="3"
                                />
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.9"
                                    fill="none"
                                    className="stroke-primary"
                                    strokeWidth="3"
                                    strokeLinecap="round"
                                    strokeDasharray={`${overallRate} ${100 - overallRate}`}
                                />
                            </svg>
                            <span className="absolute text-2xl font-bold tabular-nums">
                                {overallRate}%
                            </span>
                        </div>
                        <p className="text-sm text-muted-foreground mt-3">
                            {summary.occupiedSpots} / {summary.totalCapacity}
                        </p>
                    </CardContent>
                </Card>

                {occupancyByAnimal.length > 0 && (
                    <Card className="lg:col-span-3">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                {t("features.my-establishments.dashboard.occupancyByAnimal")}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t("common.fields.animalType")}</TableHead>
                                        <TableHead className="text-end">
                                            {t(
                                                "features.my-establishments.dashboard.occupiedSpots",
                                            )}
                                        </TableHead>
                                        <TableHead className="w-[40%]">
                                            {t(
                                                "features.my-establishments.dashboard.occupancyRate",
                                            )}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {occupancyByAnimal.map((item) => (
                                        <TableRow key={item.animalType.id}>
                                            <TableCell className="font-medium">
                                                {item.animalType.name}
                                            </TableCell>
                                            <TableCell className="text-end tabular-nums text-muted-foreground">
                                                {item.occupiedSpots} / {item.maxCapacity}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <Progress
                                                        value={Math.min(item.occupancyRate, 100)}
                                                        className="h-2"
                                                    />
                                                    <span className="text-sm font-medium tabular-nums min-w-[3rem] text-end">
                                                        {item.occupancyRate}%
                                                    </span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}
