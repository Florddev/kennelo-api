import { test, expect } from "../support/fixtures";
import { localePath, loginViaUi } from "../support/selectors";

test.describe("Profile (seeded user)", () => {
    test("shows the profile hub", async ({ page }) => {
        await page.goto(localePath("/profile"));
        await expect(page.locator("h1")).toContainText("Profile", { timeout: 15000 });
    });

    test("shows the account settings (about) page", async ({ page }) => {
        await page.goto(localePath("/settings/about"));
        await expect(page.locator("h1")).toContainText("Account settings", { timeout: 15000 });
        await expect(page.getByRole("textbox", { name: "First Name" })).toBeVisible({
            timeout: 15000,
        });
        await expect(page.getByRole("textbox", { name: "Email" })).toHaveValue(/.+@.+/, {
            timeout: 15000,
        });
    });

    test("updates the display name and persists it", async ({ page, api }) => {
        const original = await api.get<{ first_name: string; last_name: string }>("/user");
        const restoreFirst = original.data.first_name;
        const restoreLast = original.data.last_name;

        await page.goto(localePath("/settings/about"));
        const firstName = page.getByPlaceholder("John");
        await expect(firstName).toBeVisible({ timeout: 15000 });
        await firstName.clear();
        await firstName.fill("E2eFirst");
        const lastName = page.getByPlaceholder("Doe");
        await lastName.clear();
        await lastName.fill("E2eLast");
        await page.getByRole("button", { name: "Update" }).first().click();

        await expect(async () => {
            const after = await api.get<{ first_name: string }>("/user");
            expect(after.data.first_name).toBe("E2eFirst");
        }).toPass({ timeout: 15000 });

        await api.put("/user/profile", { first_name: restoreFirst, last_name: restoreLast });
    });
});

test.describe("Delete account (ephemeral user, UI)", () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test("deletes the account and cannot log in afterwards", async ({ page, ephemeralUser }) => {
        const user = await ephemeralUser();
        await loginViaUi(page, user.email, user.password);

        await page.goto(localePath("/settings/about"));
        await expect(page.getByRole("textbox", { name: "First Name" })).toBeVisible({
            timeout: 15000,
        });

        await page.getByRole("button", { name: /delete account/i }).click();
        const dialog = page.getByRole("alertdialog");
        await expect(dialog).toBeVisible({ timeout: 5000 });
        await dialog.getByRole("button", { name: /delete account/i }).click();

        await expect(page).toHaveURL(/\/login/, { timeout: 15000 });

        await expect(async () => {
            const res = await fetch("http://localhost:8000/api/login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email: user.email, password: user.password }),
            });
            expect(res.status).toBeGreaterThanOrEqual(400);
        }).toPass({ timeout: 10000 });
    });
});
