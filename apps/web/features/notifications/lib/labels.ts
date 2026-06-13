import type { NotificationModel, NotificationType } from "@workspace/modules/notifications";

type AppRoutes = (typeof import("@/lib/routes"))["routes"];

export type RenderedNotification = {
    title: string;
    body?: string;
    href?: string;
};

type Translator = (key: string, values?: Record<string, string>) => string;

type HrefKind = "booking" | "messages";

type NotificationConfig = {
    body?: boolean;
    href?: HrefKind;
};

const NOTIFICATION_CONFIG: Partial<Record<NotificationType, NotificationConfig>> = {
    booking_created: { body: true, href: "booking" },
    booking_confirmed: { body: true, href: "booking" },
    booking_rejected: { body: true, href: "booking" },
    booking_completed: { body: true, href: "booking" },
    booking_cancelled_by_client: { body: true, href: "booking" },
    payment_succeeded: { href: "booking" },
    payment_failed: { href: "booking" },
    payment_processing: { href: "booking" },
    payment_refunded: { href: "booking" },
    stripe_account_activated: {},
    new_message: { href: "messages" },
    review_received: {},
    review_published: {},
    review_response: {},
    review_reported: {},
    review_report_resolved: {},
    identity_submitted: {},
    identity_approved: {},
    identity_rejected: {},
    account_status_changed: {},
};

function resolveHref(
    kind: HrefKind | undefined,
    n: NotificationModel,
    routes?: AppRoutes,
): string | undefined {
    if (!routes || !kind) return undefined;
    if (kind === "messages") return routes.Messages();
    const bookingId = n.str("booking_id");
    return bookingId ? routes.BookingDetail({ id: bookingId }) : undefined;
}

export function renderNotification(
    n: NotificationModel,
    t: Translator,
    routes?: AppRoutes,
): RenderedNotification {
    const config = NOTIFICATION_CONFIG[n.type];
    if (!config) return { title: t("generic.title") };

    return {
        title: t(`${n.type}.title`),
        body: config.body
            ? t(`${n.type}.body`, { activity: n.str("activity_name") ?? "" })
            : undefined,
        href: resolveHref(config.href, n, routes),
    };
}
