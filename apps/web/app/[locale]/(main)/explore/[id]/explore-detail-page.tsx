"use client";

import { useState } from "react";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { ArrowLeft, Star, MapPin } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { Calendar } from "@workspace/ui/components/calendar";
import { cn } from "@workspace/ui/lib/utils";
import { CapacityModel, EstablishmentModel } from "@workspace/modules/establishments";
import { AddressModel } from "@workspace/modules/address";
import { UserModel } from "@workspace/modules/users";
import type { DateRange } from "react-day-picker";

import { useNavigation } from "@/hooks/use-navigation";
import {
    HeartButton,
    VerifiedBanner,
    SpeciesList,
    ReviewList,
    useExploreEstablishment,
    demoImage,
    demoRating,
    demoReviewsCount,
    demoFallbackPrice,
    demoYearsHosting,
} from "@/features/explore";

const DESCRIPTION_MAX = 220;
const T_LOCATION = "features.explore.detail.location" as const;
const SECONDARY_BUTTON_CLASS =
    "h-10 w-full rounded-md bg-zinc-100 text-sm font-medium text-zinc-900 hover:bg-zinc-200";

export default function ExploreDetailPage() {
    const t = useTranslations();
    const { router, params } = useNavigation<{ id: string }>();
    const id = params.id ?? "";
    const { establishment, capacities, isLoading } = useExploreEstablishment(id);

    if (isLoading) {
        return <DetailSkeleton />;
    }

    if (!establishment) {
        return (
            <div className="flex flex-col items-center justify-center gap-3 p-8 text-center">
                <h1 className="text-lg font-semibold">{t("features.explore.detail.notFound")}</h1>
                <p className="text-sm text-muted-foreground">
                    {t("features.explore.detail.notFoundDescription")}
                </p>
                <Button onClick={() => router.back()} variant="outline">
                    {t("features.explore.detail.back")}
                </Button>
            </div>
        );
    }

    return (
        <EstablishmentDetailContent
            establishment={establishment}
            capacities={capacities}
            onBack={() => router.back()}
        />
    );
}

function buildDefaultRange(seed: string): DateRange {
    const hash = Math.abs(
        [...seed].reduce((acc, char) => (Math.imul(acc, 31) + char.charCodeAt(0)) | 0, 0),
    );
    const from = new Date();
    from.setDate(from.getDate() + 14 + (hash % 20));
    from.setHours(0, 0, 0, 0);
    const to = new Date(from);
    to.setDate(to.getDate() + 7);
    return { from, to };
}

function EstablishmentDetailContent({
    establishment,
    capacities,
    onBack,
}: {
    establishment: EstablishmentModel;
    capacities: CapacityModel[];
    onBack: () => void;
}) {
    const t = useTranslations();
    const { router, routes } = useNavigation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>(() =>
        buildDefaultRange(establishment.id),
    );

    const handleBook = () => {
        if (!dateRange?.from || !dateRange?.to) return;
        router.push(
            routes.ExploreBook({
                id: establishment.id,
                search_params: {
                    from: toIsoDate(dateRange.from),
                    to: toIsoDate(dateRange.to),
                },
            }),
        );
    };
    const rating = demoRating(establishment.id);
    const reviewsCount = demoReviewsCount(establishment.id);
    const price = demoFallbackPrice(establishment.id);
    const yearsHosting = demoYearsHosting(establishment.id);
    const heroImage = demoImage(establishment.id, 800, 600, 1);
    const mapImage = demoImage(establishment.id, 800, 600, 2);

    return (
        <div className="relative flex flex-col bg-white pb-[140px] md:pb-20">
            <HeroSection image={heroImage} name={establishment.name} onBack={onBack} />

            <div className="relative -mt-8 flex flex-col gap-1 rounded-t-[32px] bg-white px-4 pt-4">
                <HeaderSection
                    name={establishment.name}
                    rating={rating}
                    address={establishment.address}
                    capacities={capacities}
                />
                <HostSection host={establishment.manager} yearsHosting={yearsHosting} />

                <section className="pt-4">
                    <VerifiedBanner />
                </section>

                <section className="flex flex-col gap-3 pt-4">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.explore.detail.speciesAccepted")}
                    </h2>
                    <SpeciesList capacities={capacities} />
                </section>

                <AboutSection description={establishment.description} />

                <Separator className="my-6" />

                <LocationSection address={establishment.address} mapImage={mapImage} />

                <Separator className="my-6" />

                <DateSection dateRange={dateRange} onDateRangeChange={setDateRange} />

                <Separator className="my-6" />

                <ReviewsSection rating={rating} reviewsCount={reviewsCount} />
            </div>

            <BookingBar
                price={price}
                dateRange={dateRange}
                canBook={Boolean(dateRange?.from && dateRange?.to)}
                onBook={handleBook}
            />
        </div>
    );
}

function toIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function computeNights(dateRange: DateRange | undefined): number {
    if (!dateRange?.from || !dateRange?.to) return 0;
    const msPerDay = 1000 * 60 * 60 * 24;
    return Math.max(0, Math.round((dateRange.to.getTime() - dateRange.from.getTime()) / msPerDay));
}

function formatDayMonth(date: Date, locale: string): string {
    return new Intl.DateTimeFormat(locale, { day: "numeric", month: "short" }).format(date);
}

function BookingBar({
    price,
    dateRange,
    canBook,
    onBook,
}: {
    price: number;
    dateRange: DateRange | undefined;
    canBook: boolean;
    onBook: () => void;
}) {
    const t = useTranslations();
    const nights = computeNights(dateRange);
    const total = nights * price;
    const hasRange = nights > 0 && dateRange?.from && dateRange?.to;
    const locale = typeof navigator !== "undefined" ? navigator.language : "fr-FR";

    return (
        <div
            className={cn(
                "fixed inset-x-0 z-20 border-t bg-background px-4 py-3",
                "bottom-13 md:bottom-0",
            )}
        >
            <div className="container mx-auto flex h-full items-center justify-between gap-4">
                <div className="flex min-w-0 flex-col gap-1">
                    {hasRange ? (
                        <>
                            <p className="text-xs text-muted-foreground">
                                {t("features.explore.detail.stayNights", { count: nights })}
                                {" · "}
                                {formatDayMonth(dateRange!.from!, locale)}-
                                {formatDayMonth(dateRange!.to!, locale)}
                            </p>
                            <p className="flex items-baseline gap-1 text-slate-900">
                                <span className="text-xl font-bold underline">{total} €</span>
                                <span className="text-sm text-muted-foreground">
                                    {t("features.explore.detail.totalSuffix")}
                                </span>
                            </p>
                        </>
                    ) : (
                        <p className="text-slate-900">
                            <span className="text-xl font-bold">{price}€</span>
                            <span className="text-sm font-medium text-muted-foreground">
                                {t("features.explore.perNight")}
                            </span>
                        </p>
                    )}
                </div>
                <Button
                    onClick={onBook}
                    disabled={!canBook}
                    className="h-14 shrink-0 rounded-full bg-foreground px-10 text-base font-medium text-background hover:bg-foreground/90 disabled:opacity-50"
                >
                    {t("features.explore.detail.book")}
                </Button>
            </div>
        </div>
    );
}

function HeaderSection({
    name,
    rating,
    address,
    capacities,
}: {
    name: string;
    rating: string;
    address: AddressModel | null;
    capacities: CapacityModel[];
}) {
    const t = useTranslations();
    const speciesLabel = capacities
        .map((capacity) => capacity.animalType.name.toLowerCase())
        .join(", ");

    let pensionLine: string | null = null;
    if (address) {
        pensionLine = speciesLabel
            ? t("features.explore.detail.pensionLabel", {
                  species: speciesLabel,
                  city: address.city,
                  country: address.country,
              })
            : t("features.explore.detail.pensionLabelFallback", {
                  city: address.city,
                  country: address.country,
              });
    }

    return (
        <section className="flex flex-col gap-3 pt-3">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-semibold text-slate-900">{name}</h1>
                <div className="flex items-center gap-1 rounded-lg px-2 py-1">
                    <Star className="size-3.5 fill-amber-400 stroke-amber-400" />
                    <span className="text-sm font-medium text-amber-500">{rating}</span>
                </div>
            </div>
            <div className="flex flex-col gap-0.5">
                {pensionLine && <p className="text-xs text-zinc-500">{pensionLine}</p>}
                <div className="flex items-center gap-1.5 text-xs text-zinc-500">
                    <span>{t("features.explore.detail.featureOutdoorPark")}</span>
                    <span className="size-1 rounded-full bg-zinc-400" />
                    <span>{t("features.explore.detail.featureIndoorYard")}</span>
                </div>
            </div>
        </section>
    );
}

function HostSection({ host, yearsHosting }: { host: UserModel; yearsHosting: number }) {
    const t = useTranslations();
    const duration = t("features.explore.detail.yearsDuration", { count: yearsHosting });

    return (
        <section className="flex items-center gap-2 pt-4">
            <Avatar size="lg">
                {host.avatarUrl && <AvatarImage src={host.avatarUrl} alt={host.getFullName()} />}
                <AvatarFallback>{host.getInitials()}</AvatarFallback>
            </Avatar>
            <div className="flex flex-1 flex-col py-0.5">
                <p className="text-sm font-medium text-black">
                    {t("features.explore.detail.host", { name: host.firstName })}
                </p>
                <p className="text-xs text-zinc-500">
                    {t("features.explore.detail.hostSince", { duration })}
                </p>
            </div>
        </section>
    );
}

function AboutSection({ description }: { description: string | null }) {
    const t = useTranslations();
    const [expanded, setExpanded] = useState(false);
    const safeDescription = description ?? "";
    const shouldTruncate = safeDescription.length > DESCRIPTION_MAX;
    const truncated = shouldTruncate
        ? `${safeDescription.slice(0, DESCRIPTION_MAX)}...`
        : safeDescription;
    const visibleDescription = expanded ? safeDescription : truncated;
    const fallbackDescription = t("features.explore.detail.aboutEmpty");
    const textToDisplay = description ? visibleDescription : fallbackDescription;

    return (
        <section className="flex flex-col gap-3 px-1 pt-4">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.explore.detail.about")}
            </h2>
            <div className="flex flex-col items-center gap-4">
                <p className="whitespace-pre-wrap text-sm text-foreground">{textToDisplay}</p>
                {shouldTruncate && (
                    <Button
                        variant="secondary"
                        className={SECONDARY_BUTTON_CLASS}
                        onClick={() => setExpanded((value) => !value)}
                    >
                        {expanded
                            ? t("features.explore.detail.readLess")
                            : t("features.explore.detail.readMore")}
                    </Button>
                )}
            </div>
        </section>
    );
}

function LocationSection({
    address,
    mapImage,
}: {
    address: AddressModel | null;
    mapImage: string;
}) {
    const t = useTranslations();
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">{t(T_LOCATION)}</h2>
            {address && (
                <p className="text-sm text-foreground">
                    {address.city}, {address.country}
                </p>
            )}
            <div className="relative aspect-[660/500] w-full overflow-hidden rounded-3xl bg-muted">
                <Image
                    src={mapImage}
                    alt={t(T_LOCATION)}
                    fill
                    sizes="(max-width: 768px) 100vw, 600px"
                    className="object-cover"
                />
                <div className="absolute inset-0 flex items-center justify-center">
                    <div className="flex items-center gap-1 rounded-full bg-background/80 px-3 py-1 text-xs font-medium shadow-md backdrop-blur-sm">
                        <MapPin className="size-3.5 text-primary" />
                        {address?.city ?? t(T_LOCATION)}
                    </div>
                </div>
            </div>
        </section>
    );
}

function DateSection({
    dateRange,
    onDateRangeChange,
}: {
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
}) {
    const t = useTranslations();

    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.explore.detail.selectDate")}
            </h2>
            <p className="text-sm text-foreground">
                {t("features.explore.detail.selectDateDescription")}
            </p>
            <div className="rounded-2xl border">
                <Calendar
                    mode="range"
                    selected={dateRange}
                    onSelect={onDateRangeChange}
                    numberOfMonths={1}
                    className="w-full"
                />
            </div>
            {dateRange && (
                <button
                    type="button"
                    className="self-start px-4 text-sm font-medium underline"
                    onClick={() => onDateRangeChange(undefined)}
                >
                    {t("features.explore.detail.clearDates")}
                </button>
            )}
        </section>
    );
}

function ReviewsSection({ rating, reviewsCount }: { rating: string; reviewsCount: number }) {
    const t = useTranslations();
    return (
        <section className="flex flex-col gap-3">
            <div className="flex items-center gap-2">
                <Star className="size-5 fill-amber-400 stroke-amber-400" />
                <h2 className="text-lg font-semibold text-slate-900">{rating}</h2>
                <span className="size-1 rounded-full bg-slate-400" />
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.explore.detail.reviews", { count: reviewsCount })}
                </h2>
            </div>
            <ReviewList />
            <Button variant="secondary" className={SECONDARY_BUTTON_CLASS}>
                {t("features.explore.detail.showAllReviews", { count: reviewsCount })}
            </Button>
        </section>
    );
}

function HeroSection({ image, name, onBack }: { image: string; name: string; onBack: () => void }) {
    return (
        <div className="relative aspect-[4/3] w-full overflow-hidden">
            <Image src={image} alt={name} fill sizes="100vw" className="object-cover" priority />
            <button
                type="button"
                onClick={onBack}
                aria-label="Back"
                className="absolute top-4 start-4 flex size-10 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm"
            >
                <ArrowLeft className="size-4" />
            </button>
            <div className="absolute top-4 end-4">
                <HeartButton size={40} iconSize={22} />
            </div>
        </div>
    );
}

function DetailSkeleton() {
    return (
        <div className="flex flex-col bg-white">
            <Skeleton className="aspect-[4/3] w-full rounded-none" />
            <div className="flex flex-col gap-4 px-4 pt-6">
                <Skeleton className="h-6 w-2/3" />
                <Skeleton className="h-4 w-1/2" />
                <Skeleton className="h-16 w-full rounded-2xl" />
                <Skeleton className="h-28 w-full rounded-3xl" />
                <Skeleton className="h-48 w-full rounded-3xl" />
            </div>
        </div>
    );
}
