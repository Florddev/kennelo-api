import { test } from "../support/fixtures";
import { localePath } from "../support/selectors";
import { crawlRoute } from "../support/crawler";

type Ids = { activityId: string };

const MANAGER_ROUTES: Array<{ name: string; path: (ids: Ids) => string; skip?: string }> = [
    { name: "HostingNow", path: () => "/hosting/now" },
    { name: "HostingCalendar", path: () => "/hosting/calendar" },
    {
        name: "HostingScan",
        path: () => "/hosting/scan",
        skip: "scan page needs scan hardware/data",
    },
    { name: "HostingMessages", path: () => "/hosting/messages" },
    { name: "MyActivities", path: () => "/hosting/host" },
    { name: "ActivityOverview", path: (ids) => `/hosting/host/${ids.activityId}/overview` },
    { name: "ActivityBookings", path: (ids) => `/hosting/host/${ids.activityId}/bookings` },
    { name: "ActivityInvoices", path: (ids) => `/hosting/host/${ids.activityId}/invoices` },
    {
        name: "ActivityInformations",
        path: (ids) => `/hosting/host/${ids.activityId}/settings/informations`,
    },
    {
        name: "ActivityAvailabilities",
        path: (ids) => `/hosting/host/${ids.activityId}/settings/availabilities`,
    },
    { name: "ActivityCycles", path: (ids) => `/hosting/host/${ids.activityId}/settings/cycles` },
    {
        name: "ActivityServices",
        path: (ids) => `/hosting/host/${ids.activityId}/settings/services`,
    },
    {
        name: "ActivityCollaborators",
        path: (ids) => `/hosting/host/${ids.activityId}/settings/collaborators`,
    },
    { name: "ActivityPayment", path: (ids) => `/hosting/host/${ids.activityId}/settings/payment` },
];

test.describe("Crawler — manager routes (en)", () => {
    for (const route of MANAGER_ROUTES) {
        test(`manager:${route.name}`, async ({ page, seededActivity }) => {
            test.skip(Boolean(route.skip), route.skip ?? "");
            await crawlRoute(
                page,
                localePath(route.path({ activityId: seededActivity.id })),
                route.name,
            );
        });
    }
});
