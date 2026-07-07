import { test, expect } from "@playwright/test";
import { localePath } from "./support/selectors";

test.describe("Mobile Navigation", () => {
    test("shows the bottom navigation on mobile", async ({ page }) => {
        await page.goto(localePath("/pets"));

        await expect(page.getByRole("link", { name: "Animals" }).first()).toBeVisible({
            timeout: 15000,
        });
        await expect(page.getByRole("link", { name: "Explore" }).first()).toBeVisible();
        await expect(page.getByRole("link", { name: "Messages" }).first()).toBeVisible();
    });

    test("bottom-nav links point to the right destinations", async ({ page }) => {
        await page.goto(localePath("/pets"));
        const exploreLink = page.getByRole("link", { name: "Explore" }).first();
        await expect(exploreLink).toBeVisible({ timeout: 15000 });
        await expect(exploreLink).toHaveAttribute("href", /\/explore/);
        await expect(page.getByRole("link", { name: "Messages" }).first()).toHaveAttribute(
            "href",
            /\/messages/,
        );
        await expect(page.getByRole("link", { name: "Animals" }).first()).toHaveAttribute(
            "href",
            /\/pets/,
        );
    });

    test("navigates to explore via direct visit", async ({ page }) => {
        await page.goto(localePath("/explore"));
        await expect(page).toHaveURL(/\/explore/, { timeout: 15000 });
        await expect(page.getByRole("link", { name: "Animals" }).first()).toBeVisible({
            timeout: 15000,
        });
    });
});
