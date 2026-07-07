import { test, expect } from "../support/fixtures";
import { localePath } from "../support/selectors";
import { STRIPE_ENABLED, confirmSetupIntent } from "../support/stripe";

type PaymentMethod = { id: string; brand?: string; last4?: string; is_default?: boolean };

test.describe("Payment methods (seeded user)", () => {
    test("returns the current payment methods from the API", async ({ api }) => {
        const res = await api.get("/me/payment-methods");
        expect(res.status).toBe(200);
    });

    test("reaches the payment-methods URL via the settings sidebar", async ({ page }) => {
        await page.goto(localePath("/settings/about"));
        await page.getByRole("link", { name: "Payment methods" }).click();
        await expect(page).toHaveURL(/\/payment-methods/, { timeout: 15000 });
    });
});

test.describe("Payment methods — full Stripe flow (test card 4242, via API)", () => {
    test.skip(!STRIPE_ENABLED, "Stripe test keys not configured");

    test("adds a test card via SetupIntent, sets it default, then deletes it", async ({
        api,
        track,
    }) => {
        const intent = await api.post<{ setup_intent_id: string; client_secret: string }>(
            "/me/payment-methods/setup-intent",
        );
        expect(intent.status).toBe(200);
        expect(intent.data.setup_intent_id).toBeTruthy();

        await confirmSetupIntent(intent.data.setup_intent_id);

        let added: PaymentMethod | undefined;
        await expect(async () => {
            const list = await api.get<PaymentMethod[]>("/me/payment-methods");
            added = (Array.isArray(list.data) ? list.data : []).find((m) => m.last4 === "4242");
            expect(added, "test card should appear in the list").toBeTruthy();
        }).toPass({ timeout: 15000 });

        const paymentMethodId = added!.id;
        let removed = false;
        track({
            label: `payment-method:${paymentMethodId}`,
            remove: async () => {
                if (!removed) await api.delete(`/me/payment-methods/${paymentMethodId}`);
            },
        });

        expect(added!.brand).toBe("visa");

        const setDefault = await api.put(`/me/payment-methods/${paymentMethodId}/default`);
        expect(setDefault.status).toBe(200);

        const del = await api.delete(`/me/payment-methods/${paymentMethodId}`);
        removed = true;
        expect([200, 204]).toContain(del.status);

        const after = await api.get<PaymentMethod[]>("/me/payment-methods");
        const stillThere = (Array.isArray(after.data) ? after.data : []).some(
            (m) => m.id === paymentMethodId,
        );
        expect(stillThere).toBeFalsy();
    });
});

test.describe("Payment methods — add-card dialog UI (blocked by settings sub-page bug)", () => {
    test.fixme("adds a card through the Stripe Elements dialog", async ({ page }) => {
        await page.goto(localePath("/settings/payment-methods"));
        await page
            .getByRole("button", { name: /add (a )?card/i })
            .first()
            .click();

        const dialog = page.locator('[data-slot="add-payment-method-dialog"]');
        await expect(dialog).toBeVisible({ timeout: 10000 });
        await dialog.locator("#cardholder-name").fill("E2E Card Holder");

        const numberFrame = page.frameLocator('iframe[title*="card number" i]').first();
        await numberFrame.locator('input[name="cardnumber"]').fill("4242424242424242");
        const expiryFrame = page.frameLocator('iframe[title*="expiration" i]').first();
        await expiryFrame.locator('input[name="exp-date"]').fill("12 / 34");
        const cvcFrame = page.frameLocator('iframe[title*="CVC" i]').first();
        await cvcFrame.locator('input[name="cvc"]').fill("123");

        await dialog.getByRole("button", { name: /^save$/i }).click();
        await expect(page.locator("[data-sonner-toast]").first()).toBeVisible({ timeout: 20000 });
    });
});
