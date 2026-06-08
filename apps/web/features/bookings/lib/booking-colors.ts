import type { BookingStatus } from "@workspace/modules/bookings";

export type ActivityColor = {
    barBg: string;
    barHoverBg: string;
    barText: string;
    dot: string;
    chipBg: string;
    chipBorder: string;
    chipText: string;
};

const ACTIVITY_PALETTE: ActivityColor[] = [
    {
        barBg: "bg-sky-500/20",
        barHoverBg: "hover:bg-sky-500/35",
        barText: "text-sky-900 dark:text-sky-100",
        dot: "bg-sky-500",
        chipBg: "bg-sky-500/15",
        chipBorder: "border-sky-500/40",
        chipText: "text-sky-700 dark:text-sky-300",
    },
    {
        barBg: "bg-violet-500/20",
        barHoverBg: "hover:bg-violet-500/35",
        barText: "text-violet-900 dark:text-violet-100",
        dot: "bg-violet-500",
        chipBg: "bg-violet-500/15",
        chipBorder: "border-violet-500/40",
        chipText: "text-violet-700 dark:text-violet-300",
    },
    {
        barBg: "bg-emerald-500/20",
        barHoverBg: "hover:bg-emerald-500/35",
        barText: "text-emerald-900 dark:text-emerald-100",
        dot: "bg-emerald-500",
        chipBg: "bg-emerald-500/15",
        chipBorder: "border-emerald-500/40",
        chipText: "text-emerald-700 dark:text-emerald-300",
    },
    {
        barBg: "bg-amber-500/20",
        barHoverBg: "hover:bg-amber-500/35",
        barText: "text-amber-900 dark:text-amber-100",
        dot: "bg-amber-500",
        chipBg: "bg-amber-500/15",
        chipBorder: "border-amber-500/40",
        chipText: "text-amber-700 dark:text-amber-300",
    },
    {
        barBg: "bg-pink-500/20",
        barHoverBg: "hover:bg-pink-500/35",
        barText: "text-pink-900 dark:text-pink-100",
        dot: "bg-pink-500",
        chipBg: "bg-pink-500/15",
        chipBorder: "border-pink-500/40",
        chipText: "text-pink-700 dark:text-pink-300",
    },
    {
        barBg: "bg-teal-500/20",
        barHoverBg: "hover:bg-teal-500/35",
        barText: "text-teal-900 dark:text-teal-100",
        dot: "bg-teal-500",
        chipBg: "bg-teal-500/15",
        chipBorder: "border-teal-500/40",
        chipText: "text-teal-700 dark:text-teal-300",
    },
];

export function activityColor(index: number): ActivityColor {
    return ACTIVITY_PALETTE[index % ACTIVITY_PALETTE.length]!;
}

export type StatusColor = {
    badge: string;
    dot: string;
};

const STATUS_COLOR_MAP: Record<BookingStatus, StatusColor> = {
    pending: {
        badge: "bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/40",
        dot: "bg-amber-500",
    },
    confirmed: {
        badge: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/40",
        dot: "bg-emerald-500",
    },
    in_progress: {
        badge: "bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/40",
        dot: "bg-sky-500",
    },
    completed: {
        badge: "bg-zinc-500/15 text-zinc-700 dark:text-zinc-300 border-zinc-500/40",
        dot: "bg-zinc-500",
    },
    cancelled: {
        badge: "bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/40",
        dot: "bg-rose-500",
    },
};

export function statusColor(status: BookingStatus): StatusColor {
    return STATUS_COLOR_MAP[status];
}

export const SELECTABLE_STATUSES: BookingStatus[] = [
    "pending",
    "confirmed",
    "in_progress",
    "completed",
    "cancelled",
];

export const DEFAULT_ACTIVE_STATUSES: BookingStatus[] = [
    "pending",
    "confirmed",
    "in_progress",
    "completed",
];
