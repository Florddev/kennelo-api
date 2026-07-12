"use client";

import { useMemo } from "react";
import { useQueries } from "@tanstack/react-query";
import { startOfDay } from "date-fns";

import { getActivityDashboard } from "@workspace/modules/activities";
import { getActivityBookings } from "@workspace/modules/bookings";
import { getActivityConversations } from "@workspace/modules/conversations";

import {
    getMonthRange,
    movementsForDay,
    type PetMovement,
} from "@/features/bookings/lib/calendar-grid";

const ACTIVE_MOVEMENT_STATUSES = new Set(["confirmed", "in_progress", "completed"]);

type OccupancyByAnimal = {
    code: string;
    name: string;
    occupied: number;
    capacity: number;
    rate: number;
};

export type HostingTodaySummary = {
    isLoading: boolean;
    revenue: {
        currentMonth: number;
        previousMonth: number;
        changeRate: number | null;
        currency: string;
        series: { month: string; amount: number }[];
    };
    present: number;
    capacity: number;
    occupancyRate: number;
    occupancyByAnimal: OccupancyByAnimal[];
    activeReservations: number;
    pendingReservations: number;
    arrivals: PetMovement[];
    departures: PetMovement[];
    unreadMessages: number;
};

export function useHostingToday(activityIds: string[], weekStartsOn: 0 | 1): HostingTodaySummary {
    const today = useMemo(() => new Date(), []);

    const { gridStart, gridEnd } = getMonthRange(today, weekStartsOn);
    const dateFrom = gridStart.toISOString().slice(0, 10);
    const dateTo = gridEnd.toISOString().slice(0, 10);

    const dashboardQueries = useQueries({
        queries: activityIds.map((activityId) => ({
            queryKey: ["hosting-today-dashboard", activityId],
            queryFn: () => getActivityDashboard(activityId),
            staleTime: 60_000,
        })),
    });

    const bookingsQueries = useQueries({
        queries: activityIds.map((activityId) => ({
            queryKey: ["hosting-today-bookings", activityId, dateFrom, dateTo],
            queryFn: () => getActivityBookings(activityId, { dateFrom, dateTo, perPage: 100 }),
            staleTime: 60_000,
        })),
    });

    const conversationQueries = useQueries({
        queries: activityIds.map((activityId) => ({
            queryKey: ["hosting-today-conversations", activityId],
            queryFn: () => getActivityConversations(activityId, { perPage: 100 }),
            staleTime: 60_000,
        })),
    });

    const isLoading =
        dashboardQueries.some((q) => q.isLoading) ||
        bookingsQueries.some((q) => q.isLoading) ||
        conversationQueries.some((q) => q.isLoading);

    return useMemo(() => {
        const dashboards = dashboardQueries.flatMap((q) => (q.data ? [q.data] : []));
        const bookings = bookingsQueries.flatMap((q) => q.data ?? []);
        const conversations = conversationQueries.flatMap((q) => q.data ?? []);

        const seriesMap = new Map<string, number>();
        let currentMonth = 0;
        let previousMonth = 0;
        let currency = "eur";
        for (const dashboard of dashboards) {
            currentMonth += dashboard.revenue.currentMonth;
            previousMonth += dashboard.revenue.previousMonth;
            currency = dashboard.revenue.currency;
            for (const point of dashboard.revenue.series) {
                seriesMap.set(point.month, (seriesMap.get(point.month) ?? 0) + point.amount);
            }
        }
        const series = [...seriesMap.entries()]
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([month, amount]) => ({ month, amount }));
        const changeRate =
            previousMonth > 0
                ? Math.round(((currentMonth - previousMonth) / previousMonth) * 1000) / 10
                : null;

        let present = 0;
        let capacity = 0;
        const animalMap = new Map<string, OccupancyByAnimal>();
        for (const dashboard of dashboards) {
            present += dashboard.summary.occupiedSpots;
            capacity += dashboard.summary.totalCapacity;
            for (const occupancy of dashboard.occupancyByAnimal) {
                const code = occupancy.animalType.code;
                const entry = animalMap.get(code) ?? {
                    code,
                    name: occupancy.animalType.name,
                    occupied: 0,
                    capacity: 0,
                    rate: 0,
                };
                entry.occupied += occupancy.occupiedSpots;
                entry.capacity += occupancy.maxCapacity;
                animalMap.set(code, entry);
            }
        }
        const occupancyByAnimal = [...animalMap.values()].map((entry) => ({
            ...entry,
            rate: entry.capacity > 0 ? Math.round((entry.occupied / entry.capacity) * 100) : 0,
        }));
        const occupancyRate = capacity > 0 ? Math.round((present / capacity) * 100) : 0;

        const todayStart = startOfDay(today);
        const activeReservations = bookings.filter((booking) => {
            const checkIn = startOfDay(new Date(booking.checkInDate));
            const checkOut = startOfDay(new Date(booking.checkOutDate));
            return (
                checkIn <= todayStart &&
                todayStart <= checkOut &&
                (booking.status === "confirmed" || booking.status === "in_progress")
            );
        }).length;
        const pendingReservations = bookings.filter(
            (booking) => booking.status === "pending",
        ).length;

        const relevantBookings = bookings.filter((booking) =>
            ACTIVE_MOVEMENT_STATUSES.has(booking.status),
        );
        const dayMovements = movementsForDay(today, relevantBookings);

        const unreadMessages = conversations.reduce(
            (sum, conversation) => sum + (conversation.unreadCount ?? 0),
            0,
        );

        return {
            isLoading,
            revenue: { currentMonth, previousMonth, changeRate, currency, series },
            present,
            capacity,
            occupancyRate,
            occupancyByAnimal,
            activeReservations,
            pendingReservations,
            arrivals: dayMovements.arrivals,
            departures: dayMovements.departures,
            unreadMessages,
        };
    }, [dashboardQueries, bookingsQueries, conversationQueries, isLoading, today]);
}
