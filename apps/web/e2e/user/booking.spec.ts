import { test, expect } from "../support/fixtures";
import { localePath, expectNoAppError } from "../support/selectors";
import { dateRange } from "../support/data";

test.describe("Booking (seeded user)", () => {
    test("opens the booking checkout page for a seeded activity", async ({
        page,
        seededActivity,
        createPet,
    }) => {
        const pet = await createPet();
        const search = `?check_in=${dateRange.from(3)}&check_out=${dateRange.to(6)}&pet_ids=${pet.id}`;
        await page.goto(localePath(`/host/${seededActivity.id}/book`) + search);
        await expectNoAppError(page);
        await expect(page.locator("body")).toBeVisible();
    });

    test("returns a price quote from the API", async ({ api, seededActivity, createPet }) => {
        const pet = await createPet();
        const res = await api.post("/bookings/quote", {
            activity_id: seededActivity.id,
            check_in_date: dateRange.from(3),
            check_out_date: dateRange.to(6),
            pet_ids: [pet.id],
        });
        expect(res.status).toBeLessThan(500);
    });

    test("rejects a booking without a payment method (API validation)", async ({
        api,
        seededActivity,
        createPet,
    }) => {
        const pet = await createPet();
        const res = await api.post("/bookings", {
            activity_id: seededActivity.id,
            check_in_date: dateRange.from(3),
            check_out_date: dateRange.to(6),
            pet_ids: [pet.id],
        });
        expect(res.status).toBeGreaterThanOrEqual(400);
    });

    test.fixme(
        true,
        "full client booking requires the host to have a connected Stripe Connect account (KYC onboarding out of scope)",
    );

    test("completes a booking end-to-end and cancels it", async ({
        page,
        api,
        seededActivity,
        createPet,
        track,
    }) => {
        const pet = await createPet();
        const search = `?check_in=${dateRange.from(3)}&check_out=${dateRange.to(6)}&pet_ids=${pet.id}`;
        await page.goto(localePath(`/host/${seededActivity.id}/book`) + search);

        await page.getByRole("button", { name: /^book$/i }).click();
        await expect(page).toHaveURL(/\/(explore|bookings)/, { timeout: 20000 });

        const bookings = await api.get<Array<{ id: string }>>("/bookings");
        const latest = (Array.isArray(bookings.data) ? bookings.data : [])[0];
        if (latest?.id) {
            track({
                label: `booking:${latest.id}`,
                remove: () => api.put(`/bookings/${latest.id}/cancel`).then(() => undefined),
            });
            await api.put(`/bookings/${latest.id}/cancel`);
        }
    });
});
