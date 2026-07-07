import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";

test.describe("Host messaging (manager)", () => {
    test("renders the host messages page", async ({ page }) => {
        await page.goto(localePath("/hosting/messages"), { waitUntil: "domcontentloaded" });
        await expectNoAppError(page);
        await expect(page.locator("h1")).toContainText("Messages", { timeout: 15000 });
    });

    test("returns host conversations for an activity via the API", async ({
        managerApi,
        seededActivity,
    }) => {
        const res = await managerApi.get(`/activities/${seededActivity.id}/conversations`);
        expect(res.status).toBe(200);
    });
});
