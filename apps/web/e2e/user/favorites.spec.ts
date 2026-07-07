import { test, expect } from "../support/fixtures";
import { localePath } from "../support/selectors";

test.describe("Favorites (seeded user)", () => {
    test("adds a favorite via API and sees it in the favorites list, then removes it", async ({
        page,
        api,
        seededActivity,
        track,
    }) => {
        await api.delete(`/favorites/${seededActivity.id}`).catch(() => undefined);

        const add = await api.post("/favorites", { activity_id: seededActivity.id });
        expect([200, 201]).toContain(add.status);
        let removed = false;
        track({
            label: `favorite:${seededActivity.id}`,
            remove: async () => {
                if (!removed) await api.delete(`/favorites/${seededActivity.id}`);
            },
        });

        await page.goto(localePath("/favorites"));
        await expect(page.locator("h1")).toContainText("Favorites", { timeout: 15000 });
        await expect(page.getByText(seededActivity.name).first()).toBeVisible({ timeout: 15000 });

        const del = await api.delete(`/favorites/${seededActivity.id}`);
        removed = true;
        expect([200, 204]).toContain(del.status);

        const list = await api.get<Array<{ id: string }>>("/favorites");
        const stillThere = (Array.isArray(list.data) ? list.data : []).some(
            (f: { id?: string; activity_id?: string }) =>
                f.id === seededActivity.id || f.activity_id === seededActivity.id,
        );
        expect(stillThere).toBeFalsy();
    });

    test("shows an empty favorites page without errors", async ({ page, api }) => {
        const current = await api.get<Array<{ id?: string; activity_id?: string }>>("/favorites");
        const ids = (Array.isArray(current.data) ? current.data : [])
            .map((f) => f.activity_id ?? f.id)
            .filter(Boolean);
        for (const id of ids) await api.delete(`/favorites/${id}`).catch(() => undefined);

        await page.goto(localePath("/favorites"));
        await expect(page.locator("h1")).toContainText("Favorites", { timeout: 15000 });
    });
});
