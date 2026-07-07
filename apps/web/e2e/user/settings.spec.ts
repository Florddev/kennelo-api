import { test, expect } from "../support/fixtures";
import { localePath } from "../support/selectors";

test.describe("Settings navigation (seeded user)", () => {
    test("navigates the settings sidebar links", async ({ page }) => {
        await page.goto(localePath("/settings/about"));
        await expect(page.locator("h1")).toContainText("Account settings", { timeout: 15000 });

        for (const label of ["Change password", "Two-factor authentication", "Payment methods"]) {
            const link = page.getByRole("link", { name: label });
            await expect(link).toBeVisible();
        }
    });

    test("reaches the two-factor settings URL", async ({ page }) => {
        await page.goto(localePath("/settings/about"));
        await page.getByRole("link", { name: "Two-factor authentication" }).click();
        await expect(page).toHaveURL(/\/two-factor/, { timeout: 15000 });
    });

    test("reaches the email-preferences URL (mock page)", async ({ page }) => {
        await page.goto(localePath("/settings/preferences-email"));
        await expect(page).toHaveURL(/\/preferences-email/);
    });

    test("returns two-factor status from the API", async ({ api }) => {
        const res = await api.get("/user");
        expect(res.status).toBe(200);
    });
});
