import { type Page, type Locator, expect } from "@playwright/test";

export const LOCALES = ["en", "fr", "ar"] as const;
export type Locale = (typeof LOCALES)[number];

export const RTL_LOCALES: Locale[] = ["ar"];

export function localePath(path: string, locale: Locale = "en"): string {
    const clean = path.startsWith("/") ? path : `/${path}`;
    return `/${locale}${clean === "/" ? "" : clean}`;
}

export function fieldErrors(page: Page): Locator {
    return page.locator('[data-slot="field-error"]');
}

export function toasts(page: Page): Locator {
    return page.locator("[data-sonner-toast]");
}

export function alerts(page: Page): Locator {
    return page.getByRole("alert");
}

export async function expectNoAppError(page: Page): Promise<void> {
    await expect(page.getByText("Application error", { exact: false })).toHaveCount(0);
    await expect(page.getByText("500", { exact: true })).toHaveCount(0);
    await expect(page.getByText("This page could not be found", { exact: false })).toHaveCount(0);
}

export const login = {
    email: (page: Page) => page.getByPlaceholder("Your email"),
    password: (page: Page) => page.getByPlaceholder("Your password").first(),
    submit: (page: Page) => page.getByRole("button", { name: "Login" }),
};

export const register = {
    firstName: (page: Page) => page.getByPlaceholder("John"),
    lastName: (page: Page) => page.getByPlaceholder("Doe"),
    email: (page: Page) => page.getByPlaceholder("Your email"),
    password: (page: Page) => page.getByPlaceholder("Your password").first(),
    confirmPassword: (page: Page) => page.getByPlaceholder("Confirm your password"),
    submit: (page: Page) => page.getByRole("button", { name: /create account/i }),
};

export const userMenu = {
    trigger: (page: Page) =>
        page
            .locator('button[aria-haspopup="menu"]')
            .filter({ has: page.locator("span[data-slot='avatar']") })
            .last(),
    item: (page: Page, name: string | RegExp) => page.getByRole("menuitem", { name }),
};

export async function loginViaUi(page: Page, email: string, password: string): Promise<void> {
    await page.goto(localePath("/login"));
    await expect(login.email(page)).toBeVisible({ timeout: 15000 });
    await login.email(page).fill(email);
    await login.password(page).fill(password);
    await login.submit(page).click();
    await expect(page).not.toHaveURL(/\/login/, { timeout: 20000 });
    await page.waitForLoadState("networkidle").catch(() => undefined);
}

export async function logoutViaUi(page: Page): Promise<void> {
    await userMenu.trigger(page).click();
    await userMenu.item(page, "Logout").click();
}
