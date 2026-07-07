import { test, expect } from "../support/fixtures";
import { ApiClient } from "../support/api-client";
import { localePath, loginViaUi } from "../support/selectors";

test.describe("Forgot / reset password (guest)", () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test("requests a reset link", async ({ page }) => {
        await page.goto(localePath("/forgot-password"));
        await page.getByPlaceholder("Your email").fill("user@orus.com");
        await page.getByRole("button", { name: /send reset link/i }).click();

        await expect(page.getByText(/reset link has been sent/i)).toBeVisible({ timeout: 10000 });
    });

    test("shows the reset-password form when a token is present", async ({ page }) => {
        await page.goto(`${localePath("/reset-password")}?token=fake-token&email=user@orus.com`);
        await expect(page.getByPlaceholder("Your password").first()).toBeVisible({
            timeout: 10000,
        });
        await expect(page.getByRole("button", { name: /reset password/i })).toBeVisible();
    });
});

test.describe("Change password — API contract (ephemeral user)", () => {
    test("changes the password via the API and logs in with the new one", async ({
        ephemeralUser,
    }) => {
        const user = await ephemeralUser();
        const newPassword = "E2eNewPass456!";

        const res = await user.api.put("/user/password", {
            current_password: user.password,
            password: newPassword,
            password_confirmation: newPassword,
        });
        expect([200, 204]).toContain(res.status);

        const relogin = await ApiClient.login({ email: user.email, password: newPassword });
        expect(relogin.tokens?.accessToken).toBeTruthy();
        await relogin.dispose();
        user.password = newPassword;
    });

    test("rejects a wrong current password via the API", async ({ ephemeralUser }) => {
        const user = await ephemeralUser();
        const res = await user.api.put("/user/password", {
            current_password: "totally-wrong",
            password: "E2eNewPass456!",
            password_confirmation: "E2eNewPass456!",
        });
        expect(res.status).toBeGreaterThanOrEqual(400);
    });
});

test.describe("Change password — UI (blocked by settings sub-page bug)", () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test.fixme("changes the password from the settings UI", async ({ page, ephemeralUser }) => {
        const user = await ephemeralUser();
        await loginViaUi(page, user.email, user.password);

        await page.goto(localePath("/settings/about"));
        await page.getByRole("link", { name: "Change password" }).click();

        const newPassword = "E2eNewPass456!";
        await expect(page.getByPlaceholder("Your new password", { exact: true })).toBeVisible({
            timeout: 15000,
        });
        await page.getByPlaceholder("Your password", { exact: true }).fill(user.password);
        await page.getByPlaceholder("Your new password", { exact: true }).fill(newPassword);
        await page.getByPlaceholder("Confirm your password", { exact: true }).fill(newPassword);
        await page.getByRole("button", { name: "Update" }).click();

        await expect(page.getByPlaceholder("Your new password", { exact: true })).toHaveValue("", {
            timeout: 15000,
        });
    });
});
