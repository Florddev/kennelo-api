import { routes } from "@/lib/routes";
import { test as setup, expect } from "@playwright/test";

const AUTH_FILE = "e2e/fixtures/.auth/user.json";

setup("authenticate as user", async ({ page }) => {
    await page.goto(routes.Login());

    await page.getByPlaceholder("Your email").fill("user@orus.com");
    await page.getByPlaceholder("Your password").fill("user");
    await page.getByRole("button", { name: "Login" }).click();

    await page.waitForURL((url) => !url.pathname.includes("/login"), {
        timeout: 10000,
    });

    await expect(page).not.toHaveURL(/\/login/);

    await page.context().storageState({ path: AUTH_FILE });
});
