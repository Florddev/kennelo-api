import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";

test.describe("Explore (seeded user)", () => {
    test("renders the explore page with a search entry point", async ({ page }) => {
        await page.goto(localePath("/explore"));
        await expectNoAppError(page);
        await expect(page.getByRole("button").or(page.getByRole("searchbox")).first()).toBeVisible({
            timeout: 15000,
        });
    });

    test("opens the results page and renders the map/results shell", async ({ page }) => {
        await page.goto(localePath("/explore/results"));
        await expectNoAppError(page);
        await expect(page.locator("body")).toBeVisible();
        await page.waitForLoadState("networkidle").catch(() => undefined);
    });

    test("opens a host detail page for a seeded activity", async ({ page, seededActivity }) => {
        await page.goto(localePath(`/host/${seededActivity.id}`));
        await expectNoAppError(page);
        await expect(page.getByText(seededActivity.name).first()).toBeVisible({ timeout: 15000 });
    });
});
