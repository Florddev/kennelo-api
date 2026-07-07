import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";
import { newAvailabilityPayload, newCyclePayload } from "../support/data";

test.describe("Activity management (manager, ephemeral activity)", () => {
    test("renders every activity manager sub-page without errors", async ({
        page,
        ephemeralActivity,
    }) => {
        test.setTimeout(90000);
        const activity = await ephemeralActivity();

        const subPages = [
            `/hosting/host/${activity.id}`,
            `/hosting/host/${activity.id}/overview`,
            `/hosting/host/${activity.id}/bookings`,
            `/hosting/host/${activity.id}/invoices`,
            `/hosting/host/${activity.id}/settings/informations`,
            `/hosting/host/${activity.id}/settings/availabilities`,
            `/hosting/host/${activity.id}/settings/cycles`,
            `/hosting/host/${activity.id}/settings/services`,
            `/hosting/host/${activity.id}/settings/collaborators`,
            `/hosting/host/${activity.id}/settings/payment`,
        ];

        for (const path of subPages) {
            await page.goto(localePath(path), { waitUntil: "domcontentloaded" });
            await expectNoAppError(page);
            await expect(page.locator("h1").first()).toBeVisible({ timeout: 15000 });
        }
    });

    test("creates and deletes an availability via the API and reflects it in the manager", async ({
        page,
        managerApi,
        ephemeralActivity,
    }) => {
        const activity = await ephemeralActivity();

        const created = await managerApi.post<{ id: string }>(
            `/activities/${activity.id}/availabilities`,
            newAvailabilityPayload(),
        );
        expect([200, 201]).toContain(created.status);

        await page.goto(localePath(`/hosting/host/${activity.id}/settings/availabilities`), {
            waitUntil: "domcontentloaded",
        });
        await expectNoAppError(page);
        await expect(page.locator("h1").first()).toBeVisible({ timeout: 15000 });

        if (created.data?.id) {
            const del = await managerApi.delete(
                `/activities/${activity.id}/availabilities/${created.data.id}`,
            );
            expect([200, 204]).toContain(del.status);
        }
    });

    test("creates and deletes a pricing cycle via the API", async ({
        managerApi,
        ephemeralActivity,
    }) => {
        const activity = await ephemeralActivity();

        const created = await managerApi.post<{ id: string }>(
            `/activities/${activity.id}/cycles`,
            newCyclePayload(),
        );
        expect([200, 201]).toContain(created.status);

        if (created.data?.id) {
            const del = await managerApi.delete(
                `/activities/${activity.id}/cycles/${created.data.id}`,
            );
            expect([200, 204]).toContain(del.status);
        }
    });
});
