import { test, expect } from "@playwright/test";
import { localePath, login, userMenu, loginViaUi } from "../support/selectors";
import { SEEDED_USER } from "../support/fixtures";

test.use({ storageState: { cookies: [], origins: [] } });

test.describe("Login", () => {
    test("logs in with valid seeded credentials", async ({ page }) => {
        await loginViaUi(page, SEEDED_USER.email, SEEDED_USER.password);
        await expect(page).not.toHaveURL(/\/login/);
        await expect(page.getByRole("link", { name: "Explore" }).first()).toBeVisible({
            timeout: 15000,
        });
    });

    test("shows an error with invalid credentials", async ({ page }) => {
        await page.goto(localePath("/login"));
        await login.email(page).fill("nobody@kennelo-e2e.test");
        await login.password(page).fill("wrong-password");
        await login.submit(page).click();

        await expect(page.getByRole("alert")).toBeVisible({ timeout: 10000 });
        await expect(page).toHaveURL(/\/login/);
    });

    test("navigates to the register page", async ({ page }) => {
        await page.goto(localePath("/login"));
        await page
            .getByRole("link", { name: /register|here|create/i })
            .first()
            .click();
        await expect(page).toHaveURL(/\/register/);
    });

    test("logs out from the user menu", async ({ page }) => {
        await loginViaUi(page, SEEDED_USER.email, SEEDED_USER.password);
        await expect(page.getByRole("link", { name: "Explore" }).first()).toBeVisible({
            timeout: 15000,
        });

        await userMenu.trigger(page).click();
        await userMenu.item(page, "Logout").click();

        await expect(page).toHaveURL(/\/login/, { timeout: 15000 });
    });

    test("redirects an authenticated user away from /login (guestOnly)", async ({ page }) => {
        await loginViaUi(page, SEEDED_USER.email, SEEDED_USER.password);
        await page.goto(localePath("/login"));
        await expect(page).not.toHaveURL(/\/login/);
    });

    test("redirects an anonymous user from a protected page to /login", async ({ page }) => {
        await page.goto(localePath("/settings/about"));
        await expect(page).toHaveURL(/\/login/);
    });
});
