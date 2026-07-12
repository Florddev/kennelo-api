"use client";

import { useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { useLocale, useTranslations } from "next-intl";
import { Magnifier } from "@solar-icons/react";
import { Cpu, History, PawPrint, ScanLine, Search } from "lucide-react";

import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";
import { cn } from "@workspace/ui/lib/utils";
import type { InCarePetModel } from "@workspace/modules/scanners";

import PageLayout from "@/components/layouts/page-layout";
import { useNavigation } from "@/hooks/use-navigation";
import { ScanHistoryList, useInCarePets, useScannerScans } from "@/features/hosting-scan";
import { useScanners } from "@/features/scanners/hooks/use-scanners";
import { ScannersSettingsPage } from "@/app/[locale]/(main)/settings/scanners/scanners-settings-page";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

const IN_CARE_NS = "features.hosting-scan.inCare";

export function HostingScanPage() {
    const t = useTranslations();
    const { scans, isLoading: scansLoading } = useScannerScans();
    const { pets: inCarePets, isLoading: inCareLoading } = useInCarePets(true);
    const { scanners } = useScanners();

    return (
        <PageLayout
            Icon={Magnifier}
            title={t("features.hosting-scan.title")}
            containerClassName="p-4 md:p-8"
        >
            <Tabs defaultValue="live" className="w-full gap-4 flex-col">
                <TabsList variant="line" className="w-full justify-start gap-6 rounded-none p-0">
                    <TabsTrigger value="live" className="max-w-fit py-2">
                        <span data-slot="tab-label" className="flex items-center gap-1.5">
                            <ScanLine className="size-4" />
                            {t("features.hosting-scan.tabs.live")}
                        </span>
                        <span data-slot="tab-indicator" />
                    </TabsTrigger>
                    <TabsTrigger value="history" className="max-w-fit py-2">
                        <span data-slot="tab-label" className="flex items-center gap-1.5">
                            <History className="size-4" />
                            {t("features.hosting-scan.tabs.history")}
                        </span>
                        <span data-slot="tab-indicator" />
                    </TabsTrigger>
                    <TabsTrigger value="devices" className="max-w-fit py-2">
                        <span data-slot="tab-label" className="flex items-center gap-1.5">
                            <Cpu className="size-4" />
                            {t("features.hosting-scan.tabs.devices")}
                        </span>
                        <span data-slot="tab-indicator" />
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="live" className="flex flex-col gap-6">
                    <ScanHero />

                    <div className="grid grid-cols-3 gap-3">
                        <StatCard
                            Icon={Cpu}
                            label={t("features.hosting-scan.stats.scanners")}
                            value={scanners.length}
                        />
                        <StatCard
                            Icon={PawPrint}
                            label={t("features.hosting-scan.stats.inCare")}
                            value={inCarePets.length}
                        />
                        <StatCard
                            Icon={History}
                            label={t("features.hosting-scan.stats.scans")}
                            value={scans.length}
                        />
                    </div>

                    <InCareSection pets={inCarePets} isLoading={inCareLoading} />
                </TabsContent>

                <TabsContent value="history">
                    <ScanHistoryList scans={scans} isLoading={scansLoading} />
                </TabsContent>

                <TabsContent value="devices">
                    <ScannersSettingsPage />
                </TabsContent>
            </Tabs>
        </PageLayout>
    );
}

function ScanHero() {
    const t = useTranslations("features.hosting-scan");
    const { push, routes } = useNavigation();
    const [chip, setChip] = useState("");

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const value = chip.trim();
        if (!value) return;
        push(routes.HostingScanResult({ microchip: value }));
    };

    return (
        <div
            data-slot="scan-hero"
            className="relative flex flex-col items-center gap-8 overflow-hidden rounded-4xl border bg-card px-6 py-12 text-center"
        >
            <div className="relative flex items-center justify-center">
                <span className="absolute size-40 rounded-full bg-primary/5 animate-ping [animation-duration:2.5s]" />
                <span className="absolute size-28 rounded-full bg-primary/10 animate-ping [animation-duration:2s]" />
                <span className="absolute size-20 rounded-full bg-primary/15" />
                <div className="relative flex size-16 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg">
                    <ScanLine className="size-8" />
                </div>
            </div>

            <div className="flex flex-col items-center gap-2">
                <div className="inline-flex items-center gap-2 rounded-4xl bg-green-500/10 px-3 py-1">
                    <span className="size-2 rounded-full bg-green-500 animate-pulse" />
                    <span className="text-xs font-medium text-green-600 dark:text-green-400">
                        {t("waiting.listening")}
                    </span>
                </div>
                <h2 className="text-2xl font-bold">{t("waiting.title")}</h2>
                <p className="max-w-sm text-sm text-muted-foreground">{t("waiting.description")}</p>
            </div>

            <form onSubmit={submit} className="flex w-full max-w-sm flex-col gap-2">
                <div className="flex items-center gap-2">
                    <div className="relative flex-1">
                        <Cpu className="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={chip}
                            onChange={(event) => setChip(event.target.value)}
                            placeholder={t("manual.placeholder")}
                            inputMode="numeric"
                            className="ps-9 rounded-4xl"
                        />
                    </div>
                    <Button type="submit" disabled={!chip.trim()} className="rounded-4xl gap-1.5">
                        <Search className="size-4" />
                        {t("manual.submit")}
                    </Button>
                </div>
                <p className="text-xs text-muted-foreground">{t("manual.hint")}</p>
            </form>
        </div>
    );
}

function StatCard({ Icon, label, value }: { Icon: typeof PawPrint; label: string; value: number }) {
    return (
        <div
            data-slot="scan-stat-card"
            className="flex items-center gap-3 rounded-2xl border bg-card p-3 md:p-4"
        >
            <span className="flex items-center justify-center size-9 rounded-full bg-primary/10 text-primary shrink-0">
                <Icon className="size-4" />
            </span>
            <div className="flex flex-col min-w-0">
                <span className="text-xl font-bold leading-none tabular-nums">{value}</span>
                <span className="text-xs text-muted-foreground truncate">{label}</span>
            </div>
        </div>
    );
}

function InCareSection({ pets, isLoading }: { pets: InCarePetModel[]; isLoading: boolean }) {
    const t = useTranslations(IN_CARE_NS);

    return (
        <section data-slot="in-care-section" className="flex flex-col gap-3">
            <div className="flex flex-col">
                <h2 className="text-xl font-semibold">{t("title")}</h2>
                <p className="text-sm text-muted-foreground">{t("description")}</p>
            </div>

            <InCareContent pets={pets} isLoading={isLoading} />
        </section>
    );
}

function InCareContent({ pets, isLoading }: { pets: InCarePetModel[]; isLoading: boolean }) {
    const t = useTranslations(IN_CARE_NS);

    if (isLoading) {
        return (
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {[0, 1, 2].map((index) => (
                    <div key={index} className="h-20 rounded-2xl border bg-card animate-pulse" />
                ))}
            </div>
        );
    }

    if (pets.length === 0) {
        return (
            <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed p-10 text-center">
                <PawPrint className="size-10 text-muted-foreground opacity-20" />
                <p className="text-sm text-muted-foreground">{t("empty")}</p>
            </div>
        );
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            {pets.map((pet) => (
                <InCarePetCard key={pet.id} pet={pet} />
            ))}
        </div>
    );
}

function InCarePetCard({ pet }: { pet: InCarePetModel }) {
    const t = useTranslations(IN_CARE_NS);
    const locale = useLocale();
    const { routes } = useNavigation();

    const dateRange = `${shortDate(pet.checkInDate, locale)} – ${shortDate(pet.checkOutDate, locale)}`;
    const stayLine = pet.activityName ? `${pet.activityName} · ${dateRange}` : dateRange;
    const showTypeBadge = pet.animalType !== null && isIllustratedType(pet.animalType.code);

    const content = (
        <div
            className={cn(
                "flex h-full items-center gap-3 rounded-2xl border bg-card p-3 transition-colors",
                pet.microchipNumber && "hover:bg-muted/40",
            )}
        >
            <div className="relative shrink-0">
                <span className="flex size-12 items-center justify-center overflow-hidden rounded-full bg-muted">
                    <PetAvatar pet={pet} />
                </span>
                {showTypeBadge && (
                    <span className="absolute -bottom-0.5 -end-0.5 flex size-5 items-center justify-center rounded-full border border-border bg-background">
                        <PetTypeIllustration
                            code={pet.animalType!.code}
                            name={pet.animalType!.name}
                            className="size-3"
                        />
                    </span>
                )}
            </div>

            <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                <div className="flex items-center gap-2">
                    <span className="truncate font-medium leading-tight">{pet.name}</span>
                    {!pet.hasMicrochip && (
                        <Badge variant="outline" size="sm" className="shrink-0">
                            {t("noChip")}
                        </Badge>
                    )}
                </div>
                {pet.microchipNumber && (
                    <span className="flex items-center gap-1 font-mono text-[11px] text-muted-foreground">
                        <Cpu className="size-3 shrink-0" />
                        <span className="truncate">{pet.microchipNumber}</span>
                    </span>
                )}
                {pet.ownerName && (
                    <span className="truncate text-xs text-muted-foreground">{pet.ownerName}</span>
                )}
                <span className="truncate text-[11px] text-muted-foreground">{stayLine}</span>
            </div>
        </div>
    );

    if (!pet.microchipNumber) {
        return content;
    }

    return (
        <Link href={routes.HostingScanResult({ microchip: pet.microchipNumber })}>{content}</Link>
    );
}

function PetAvatar({ pet }: { pet: InCarePetModel }) {
    if (pet.avatarUrl) {
        return (
            <Image
                src={pet.avatarUrl}
                alt={pet.name}
                width={48}
                height={48}
                className="size-full object-cover"
            />
        );
    }

    return <PawPrint className="size-5 text-muted-foreground" />;
}

function shortDate(value: string, locale: string): string {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return new Intl.DateTimeFormat(locale, { day: "numeric", month: "short" }).format(date);
}
