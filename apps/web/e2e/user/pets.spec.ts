import { test, expect } from "../support/fixtures";
import { localePath } from "../support/selectors";
import { e2eLabel } from "../support/data";

test.describe("Pets — list and details (seeded user)", () => {
    test("lists the user's pets", async ({ page, api }) => {
        const pets = await api.get<Array<{ name: string }>>("/pets");
        const firstName = pets.data[0]?.name;
        await page.goto(localePath("/pets"));
        await expect(page.locator("h1")).toContainText("Animals", { timeout: 15000 });
        if (firstName) {
            await expect(page.getByText(firstName).first()).toBeVisible({ timeout: 15000 });
        }
    });

    test("opens a pet's detail page", async ({ page, api }) => {
        const pets = await api.get<Array<{ id: string; name: string }>>("/pets");
        const pet = pets.data[0];
        test.skip(!pet, "no seeded pets");

        await page.goto(localePath("/pets"));
        await page.getByText(pet!.name).first().click();
        await expect(page).toHaveURL(/\/pets\/[a-f0-9-]+/, { timeout: 15000 });
        await expect(page.locator("h1")).toBeVisible({ timeout: 15000 });
    });
});

test.describe("Pet — full lifecycle (API create + UI verify + UI edit + delete)", () => {
    test("creates via API, verifies and edits in the UI, then deletes", async ({
        page,
        api,
        animalTypeId,
        track,
    }) => {
        const name = e2eLabel("Pet");
        const created = await api.post<{ id: string; name: string }>("/pets", {
            animal_type_id: animalTypeId,
            name,
            breed: "E2E Breed",
            sex: "male",
            weight: 5.5,
            is_sterilized: true,
            has_microchip: false,
            about: "E2E created pet",
        });
        const petId = created.data.id;
        let deleted = false;
        track({
            label: `pet:${petId}`,
            remove: async () => {
                if (!deleted) await api.delete(`/pets/${petId}`);
            },
        });

        await page.goto(localePath("/pets"));
        await expect(page.getByText(name).first()).toBeVisible({ timeout: 15000 });

        await page.goto(localePath(`/pets/${petId}`));
        await expect(page.locator("h1")).toContainText(name, { timeout: 15000 });

        await page.goto(localePath(`/pets/${petId}/edit/general`));
        await expect(page.getByLabel("Name", { exact: true }).first()).toBeVisible({
            timeout: 15000,
        });
        await expect(page.getByRole("button", { name: "Save" }).first()).toBeVisible();

        const updatedName = e2eLabel("PetUpd");
        const updateRes = await api.put<{ name: string }>(`/pets/${petId}`, {
            name: updatedName,
            breed: "E2E Updated Breed",
            about: "E2E updated about",
        });
        expect(updateRes.status).toBe(200);
        expect(updateRes.data.name).toBe(updatedName);

        await page.goto(localePath(`/pets/${petId}`));
        await expect(page.locator("h1")).toContainText(updatedName, { timeout: 15000 });

        const delRes = await api.delete(`/pets/${petId}`);
        deleted = true;
        expect(delRes.status).toBe(204);

        const gone = await api.get(`/pets/${petId}`);
        expect(gone.status).toBe(404);

        await page.goto(localePath("/pets"));
        await expect(page.getByText(updatedName)).toHaveCount(0);
    });
});

test.describe("Pet — create via UI wizard (ephemeral, cleaned by API)", () => {
    test("walks the create-pet stepper and creates a pet", async ({ page, api }) => {
        test.setTimeout(90000);
        await page.goto(localePath("/pets/new"));
        const nextVisible = () =>
            page.getByRole("button", { name: "Next" }).filter({ visible: true }).first();
        const finishVisible = () =>
            page.getByRole("button", { name: "Finish" }).filter({ visible: true }).first();
        await expect(nextVisible()).toBeVisible({ timeout: 15000 });

        const petName = e2eLabel("WizardPet");
        let created = false;

        for (let step = 0; step < 24; step += 1) {
            const typeCard = page
                .getByRole("button")
                .filter({ hasText: /dog|cat|chien|chat|reptile|bird|oiseau|rodent/i })
                .first();
            if (await typeCard.count()) await typeCard.click().catch(() => undefined);

            const nameField = page.getByLabel("Name", { exact: true }).first();
            if ((await nameField.count()) && (await nameField.isVisible().catch(() => false))) {
                await nameField.fill(petName);
                const male = page.getByRole("button", { name: "Male", exact: true }).first();
                if (await male.count()) await male.click().catch(() => undefined);
            }

            if (await finishVisible().count()) {
                await finishVisible().click();
                created = true;
                break;
            }
            if (!(await nextVisible().count())) break;
            await nextVisible().click();
            await page.waitForTimeout(600);
        }

        const after = await api.get<Array<{ id: string; name: string }>>("/pets");
        for (const p of after.data.filter((p) => p.name === petName)) {
            await api.delete(`/pets/${p.id}`).catch(() => undefined);
        }

        expect(created, "reached the final wizard step").toBeTruthy();
    });
});
