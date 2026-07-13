import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";
import { newActivityPayload } from "../support/data";
import { verifyUserEmail } from "../support/db";

test.describe("Become a host — wizard (manager)", () => {
    test("renders the become-host wizard", async ({ page }) => {
        await page.goto(localePath("/become-host"));
        await expectNoAppError(page);
        await expect(
            page.getByRole("button", { name: /next|get started|create my activity/i }).first(),
        ).toBeVisible({
            timeout: 15000,
        });
    });

    test("walks the wizard and creates an activity", async ({ page, managerApi }) => {
        test.setTimeout(90000);
        const before = await managerApi.get<Array<{ id: string }>>("/activities", { per_page: 50 });
        const beforeIds = new Set((Array.isArray(before.data) ? before.data : []).map((a) => a.id));

        await page.goto(localePath("/become-host"));
        const nextVisible = () =>
            page.getByRole("button", { name: "Next" }).filter({ visible: true }).first();
        const submitVisible = () =>
            page
                .getByRole("button", { name: /create my activity/i })
                .filter({ visible: true })
                .first();
        const getStarted = page
            .getByRole("button", { name: /get started/i })
            .filter({ visible: true })
            .first();
        if (await getStarted.count()) await getStarted.click().catch(() => undefined);

        const activityName = `E2E-Activity-${Date.now()}`;

        for (let step = 0; step < 16; step += 1) {
            const typeCard = page.getByText("Boarding", { exact: true }).first();
            if ((await typeCard.count()) && (await typeCard.isVisible().catch(() => false))) {
                await typeCard.click().catch(() => undefined);
            }

            const nameField = page.getByLabel("Activity name", { exact: true }).first();
            if ((await nameField.count()) && (await nameField.isVisible().catch(() => false))) {
                await nameField.fill(activityName);
                const desc = page.getByLabel("Description", { exact: true }).first();
                if (await desc.count()) await desc.fill("E2E generated activity");
            }

            const line1 = page.getByLabel("Address line 1", { exact: true }).first();
            if ((await line1.count()) && (await line1.isVisible().catch(() => false))) {
                await line1.fill("1 Rue de Test");
                await page.getByLabel("City", { exact: true }).first().fill("Paris");
                await page.getByLabel("Postal code", { exact: true }).first().fill("75001");
                await page.getByLabel("Country", { exact: true }).first().fill("France");
            }

            if (await submitVisible().count()) {
                await submitVisible().click();
                break;
            }
            if (!(await nextVisible().count())) break;
            await nextVisible().click();
            await page.waitForTimeout(500);
        }

        await expect(async () => {
            const after = await managerApi.get<Array<{ id: string }>>("/activities", {
                per_page: 50,
            });
            const fresh = (Array.isArray(after.data) ? after.data : []).find(
                (a) => !beforeIds.has(a.id),
            );
            expect(fresh).toBeTruthy();
        }).toPass({ timeout: 20000 });

        const after = await managerApi.get<Array<{ id: string; name: string }>>("/activities", {
            per_page: 50,
        });
        const fresh = (Array.isArray(after.data) ? after.data : []).find(
            (a) => !beforeIds.has(a.id),
        );
        if (fresh) await managerApi.delete(`/activities/${fresh.id}`).catch(() => undefined);
    });

    test("a freshly registered, verified user can create their first activity and becomes a manager", async ({
        ephemeralUser,
    }) => {
        const user = await ephemeralUser();
        verifyUserEmail(user.email);

        const created = await user.api.post<{ id: string; name: string }>(
            "/activities",
            newActivityPayload(),
        );
        expect(created.status).toBe(201);

        const me = await user.api.get<{ roles: string[] }>("/user");
        expect(me.data.roles).toContain("manager");

        if (created.data?.id) {
            await user.api.delete(`/activities/${created.data.id}`).catch(() => undefined);
        }
    });
});
