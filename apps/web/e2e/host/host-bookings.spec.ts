import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";

test.describe("Host bookings & calendar (manager)", () => {
    test("renders the hosting calendar", async ({ page }) => {
        await page.goto(localePath("/hosting/calendar"), { waitUntil: "domcontentloaded" });
        await expectNoAppError(page);
        await expect(page.locator("h1")).toContainText("Calendar", { timeout: 15000 });
    });

    test("renders the hosting today dashboard", async ({ page }) => {
        await page.goto(localePath("/hosting/now"), { waitUntil: "domcontentloaded" });
        await expectNoAppError(page);
        await expect(page.locator("h1")).toContainText("Today", { timeout: 15000 });
    });

    test("renders the my-activities selector", async ({ page }) => {
        await page.goto(localePath("/hosting/host"), { waitUntil: "domcontentloaded" });
        await expectNoAppError(page);
        await expect(page.locator("h1")).toBeVisible({ timeout: 15000 });
    });

    test.fixme("lists activity bookings via the API (known 500 bug)", async ({
        managerApi,
        seededActivity,
    }) => {
        const res = await managerApi.get(`/activities/${seededActivity.id}/bookings`);
        expect(res.status).toBe(200);
    });

    test("renders the activity bookings table", async ({ page, seededActivity }) => {
        await page.goto(localePath(`/hosting/host/${seededActivity.id}/bookings`), {
            waitUntil: "domcontentloaded",
        });
        await expectNoAppError(page);
        await expect(page.locator("h1").first()).toBeVisible({ timeout: 15000 });
    });
});
