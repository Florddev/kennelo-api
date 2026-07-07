import { test as setup, expect } from "@playwright/test";
import { localePath, login } from "../support/selectors";
import { SEEDED_MANAGER } from "../support/fixtures";

const AUTH_FILE = "e2e/fixtures/.auth/manager.json";

setup("authenticate as manager", async ({ page }) => {
    await page.goto(localePath("/login"));

    await login.email(page).fill(SEEDED_MANAGER.email);
    await login.password(page).fill(SEEDED_MANAGER.password);
    await login.submit(page).click();

    await page.waitForURL((url) => !url.pathname.includes("/login"), { timeout: 15000 });
    await expect(page).not.toHaveURL(/\/login/);

    await page.context().storageState({ path: AUTH_FILE });
});
