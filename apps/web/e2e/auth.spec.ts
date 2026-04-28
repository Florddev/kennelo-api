import { routes } from "@/lib/routes";
import { test, expect } from "@playwright/test";

test.use({ storageState: { cookies: [], origins: [] } });

const LOGIN_URL = routes.Login();
const REGISTER_URL = routes.Register();
const PLACEHOLDER_EMAIL = "Your email";
const PLACEHOLDER_PASSWORD = "Your password";
const SEEDED_EMAIL = "user@orus.com";
const SEEDED_PASSWORD = "user";
test.describe("Login", () => {
    test("should login with valid credentials and redirect to home", async ({ page }) => {
        await page.goto(LOGIN_URL);

        await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(SEEDED_EMAIL);
        await page.getByPlaceholder(PLACEHOLDER_PASSWORD).fill(SEEDED_PASSWORD);
        await page.getByRole("button", { name: "Login" }).click();

        await expect(page).not.toHaveURL(/\/login/, { timeout: 15000 });
    });

    test("should show error with invalid credentials", async ({ page }) => {
        await page.goto(LOGIN_URL);

        await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(SEEDED_EMAIL);
        await page.getByPlaceholder(PLACEHOLDER_PASSWORD).fill("wrongpassword");
        await page.getByRole("button", { name: "Login" }).click();

        await expect(page.getByRole("alert")).toBeVisible({ timeout: 10000 });
    });

    test("should navigate to register page", async ({ page }) => {
        await page.goto(LOGIN_URL);

        await page.getByRole("link", { name: /register/i }).click();

        await expect(page).toHaveURL(/\/register/);
    });
});

test.describe("Register", () => {
    test("should register a new account and redirect to home", async ({ page }) => {
        const uniqueEmail = `test-${Date.now()}@example.com`;

        await page.goto(REGISTER_URL);

        await page.getByPlaceholder("John").fill("Test");
        await page.getByPlaceholder("Doe").fill("User");
        await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(uniqueEmail);
        await page.getByPlaceholder(PLACEHOLDER_PASSWORD).first().fill("Password123!");
        await page.getByPlaceholder("Confirm your password").fill("Password123!");
        await page.getByRole("button", { name: /create account/i }).click();

        await expect(page).not.toHaveURL(/\/register/, { timeout: 15000 });
    });
});

test.describe("Logout", () => {
    test("should logout and redirect away from app", async ({ page }) => {
        await page.goto(LOGIN_URL);
        await page.getByPlaceholder(PLACEHOLDER_EMAIL).fill(SEEDED_EMAIL);
        await page.getByPlaceholder(PLACEHOLDER_PASSWORD).fill(SEEDED_PASSWORD);
        await page.getByRole("button", { name: "Login" }).click();
        await expect(page).not.toHaveURL(/\/login/, { timeout: 15000 });

        await page.getByRole("button", { name: /^(UA|U)$/i }).click();
        await page.getByRole("menuitem", { name: "Logout" }).click();

        await page.waitForTimeout(2000);
        const url = page.url();
        expect(url).not.toContain("/s/my/");
    });
});
