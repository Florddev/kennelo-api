import { routes } from "@/lib/routes";
import { test, expect, type Page } from "@playwright/test";

test.use({ storageState: { cookies: [], origins: [] } });

const LOGIN_URL = routes.Login();
const PROFILE_URL = routes.MyProfileAbout();
const CHANGE_PASSWORD_URL = routes.MyProfileChangePassword();
const PLACEHOLDER_EMAIL = "Your email";
const PLACEHOLDER_PASSWORD = "Your password";

const TEST_USER = {
    firstName: "E2eTest",
    lastName: "Lifecycle",
    email: `e2e-lifecycle-${Date.now()}@test.com`,
    password: "TestPass123!",
};

async function loginViaUI(page: Page, email: string, password: string) {
    await page.goto(LOGIN_URL);
    await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(email);
    await page.getByPlaceholder(PLACEHOLDER_PASSWORD).fill(password);
    await page.getByRole("button", { name: "Login" }).click();
    await expect(page).not.toHaveURL(/\/login/, { timeout: 15000 });
}

test.describe.serial("User Full Lifecycle (CRUD)", () => {
    test("1. Register a new user via UI", async ({ page }) => {
        await page.goto("/s/accounts/register");

        await page.getByPlaceholder("John").fill(TEST_USER.firstName);
        await page.getByPlaceholder("Doe").fill(TEST_USER.lastName);
        await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(TEST_USER.email);
        await page.getByPlaceholder(PLACEHOLDER_PASSWORD).first().fill(TEST_USER.password);
        await page.getByPlaceholder("Confirm your password").fill(TEST_USER.password);
        await page.getByRole("button", { name: /create account/i }).click();

        await expect(page).not.toHaveURL(/\/register/, { timeout: 15000 });
    });

    test("2. Login with the new user", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);
    });

    test("3. Update profile (first name, last name) via UI", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);

        await page.goto(PROFILE_URL);
        await expect(page.getByRole("textbox", { name: "First Name" })).toHaveValue(/.+/, {
            timeout: 15000,
        });

        const firstNameInput = page.getByPlaceholder("John");
        await firstNameInput.clear();
        await firstNameInput.fill("Updated");

        const lastNameInput = page.getByPlaceholder("Doe");
        await lastNameInput.clear();
        await lastNameInput.fill("Name");

        await page
            .getByRole("button", { name: /update/i })
            .first()
            .click();

        await page.waitForTimeout(2000);

        await expect(page.getByPlaceholder("John")).toHaveValue("Updated");
        await expect(page.getByPlaceholder("Doe")).toHaveValue("Name");
    });

    test("4. Change password via UI", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);

        await page.goto(CHANGE_PASSWORD_URL);
        await expect(page.getByRole("textbox", { name: "Password", exact: true })).toBeVisible({
            timeout: 10000,
        });

        const newPassword = "NewTestPass456!";
        await page.getByRole("textbox", { name: "Password", exact: true }).fill(TEST_USER.password);
        await page.getByRole("textbox", { name: "New Password" }).fill(newPassword);
        await page.getByRole("textbox", { name: "Confirm Password" }).fill(newPassword);
        await page.getByRole("button", { name: /update/i }).click();

        await expect(page.getByRole("textbox", { name: "Password", exact: true })).toHaveValue("", {
            timeout: 15000,
        });

        TEST_USER.password = newPassword;
    });

    test("5. Login with new password", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);
    });

    test("6. Change email via UI", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);

        const newEmail = `e2e-updated-${Date.now()}@test.com`;
        await page.goto(PROFILE_URL);
        await expect(page.getByRole("textbox", { name: "First Name" })).toHaveValue(/.+/, {
            timeout: 15000,
        });

        const emailInput = page.getByRole("textbox", { name: "Email" });
        await emailInput.clear();
        await emailInput.fill(newEmail);

        const passwordField = page
            .locator("form")
            .filter({ has: page.getByRole("textbox", { name: "Email" }) })
            .getByPlaceholder(PLACEHOLDER_PASSWORD);
        await passwordField.fill(TEST_USER.password);

        await page.getByRole("button", { name: /save/i }).click();

        const emailFormPassword = page
            .locator("form")
            .filter({ has: page.getByRole("textbox", { name: "Email" }) })
            .getByPlaceholder(PLACEHOLDER_PASSWORD);
        await expect(emailFormPassword).toHaveValue("", { timeout: 15000 });
        await expect(page.getByRole("textbox", { name: "Email" })).toHaveValue(newEmail);

        TEST_USER.email = newEmail;
    });

    test("7. Login with new email", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);
    });

    test("8. Delete account via UI and verify cannot login", async ({ page }) => {
        await loginViaUI(page, TEST_USER.email, TEST_USER.password);

        await page.goto(PROFILE_URL);
        await expect(page.getByRole("textbox", { name: "First Name" })).toHaveValue(/.+/, {
            timeout: 15000,
        });

        await page.getByRole("button", { name: /delete account/i }).click();

        await expect(page.getByRole("alertdialog")).toBeVisible({ timeout: 5000 });
        await page
            .getByRole("alertdialog")
            .getByRole("button", { name: /delete account/i })
            .click();

        await page.waitForTimeout(3000);

        await page.goto(LOGIN_URL);
        await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(TEST_USER.email);
        await page.getByPlaceholder(PLACEHOLDER_PASSWORD).fill(TEST_USER.password);
        await page.getByRole("button", { name: "Login" }).click();

        await expect(page.getByRole("alert")).toBeVisible({ timeout: 10000 });
    });
});
