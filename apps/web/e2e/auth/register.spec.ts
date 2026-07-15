import { test, expect } from "../support/fixtures";
import { localePath, register } from "../support/selectors";
import { newUserPayload, E2E_STRONG_PASSWORD } from "../support/data";
import { ApiClient } from "../support/api-client";

test.use({ storageState: { cookies: [], origins: [] } });

test.describe("Register", () => {
    test("creates a new account and lands authenticated", async ({ page }) => {
        const user = newUserPayload();

        await page.goto(localePath("/register"));
        await register.firstName(page).fill(user.firstName);
        await register.lastName(page).fill(user.lastName);
        await register.email(page).fill(user.email);
        await register.password(page).fill(user.password);
        await register.confirmPassword(page).fill(user.password);
        await register.submit(page).click();

        await expect(page).not.toHaveURL(/\/register/, { timeout: 15000 });

        const client = await ApiClient.login({ email: user.email, password: user.password });
        await client.delete("/user");
        await client.dispose();
    });

    test("shows validation errors on empty submit", async ({ page }) => {
        await page.goto(localePath("/register"));
        await register.submit(page).click();

        await expect(
            page.locator('[data-slot="field-error"]').or(page.getByRole("alert")).first(),
        ).toBeVisible({ timeout: 10000 });
        await expect(page).toHaveURL(/\/register/);
    });

    test("rejects a duplicate email", async ({ page }) => {
        await page.goto(localePath("/register"));
        await register.firstName(page).fill("Dup");
        await register.lastName(page).fill("User");
        await register.email(page).fill("user@orus.com");
        await register.password(page).fill(E2E_STRONG_PASSWORD);
        await register.confirmPassword(page).fill(E2E_STRONG_PASSWORD);
        await register.submit(page).click();

        await expect(page).toHaveURL(/\/register/);
    });
});
