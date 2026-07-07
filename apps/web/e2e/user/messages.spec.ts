import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";

test.describe("Messages (seeded user)", () => {
    test("renders the conversations page", async ({ page }) => {
        await page.goto(localePath("/messages"));
        await expectNoAppError(page);
        await expect(page.locator("body")).toBeVisible();
        await page.waitForLoadState("networkidle").catch(() => undefined);
    });

    test("returns conversations and unread-count from the API", async ({ api }) => {
        const conversations = await api.get("/conversations");
        expect(conversations.status).toBe(200);
        const unread = await api.get("/conversations/unread-count");
        expect(unread.status).toBe(200);
    });

    test("opens a conversation from the list when one exists", async ({ page, api }) => {
        const res = await api.get<Array<{ id: string }>>("/conversations");
        const conversations = Array.isArray(res.data) ? res.data : [];
        const first = conversations[0];
        test.skip(!first, "no seeded conversations for this user");

        await page.goto(`${localePath("/messages")}?conversation_id=${first!.id}`);
        await expectNoAppError(page);
        await expect(page.locator("body")).toBeVisible();
    });
});
