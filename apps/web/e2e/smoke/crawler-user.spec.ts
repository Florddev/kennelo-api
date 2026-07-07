import { test } from "../support/fixtures";
import { LOCALES, type Locale, localePath, expectNoAppError } from "../support/selectors";
import { attachErrorCollectors, crawlRoute } from "../support/crawler";
import { expect } from "@playwright/test";

type Ids = { petId: string; activityId: string };

const USER_ROUTES: Array<{ name: string; path: (ids: Ids) => string }> = [
    { name: "Home", path: () => "/" },
    { name: "Explore", path: () => "/explore" },
    { name: "ExploreResults", path: () => "/explore/results" },
    { name: "Favorites", path: () => "/favorites" },
    { name: "MyPets", path: () => "/pets" },
    { name: "NewPet", path: () => "/pets/new" },
    { name: "PetDetails", path: (ids) => `/pets/${ids.petId}` },
    { name: "PetEditGeneral", path: (ids) => `/pets/${ids.petId}/edit/general` },
    { name: "PetEditHealth", path: (ids) => `/pets/${ids.petId}/edit/health` },
    { name: "PetEditPersonality", path: (ids) => `/pets/${ids.petId}/edit/personality` },
    { name: "PetEditPhotos", path: (ids) => `/pets/${ids.petId}/edit/photos` },
    { name: "LivePet", path: () => "/pets/live" },
    { name: "HostDetail", path: (ids) => `/host/${ids.activityId}` },
    { name: "Messages", path: () => "/messages" },
    { name: "Notifications", path: () => "/notifications" },
    { name: "Profile", path: () => "/profile" },
    { name: "SettingsAbout", path: () => "/settings/about" },
    { name: "BecomeHost", path: () => "/become-host" },
];

test.describe("Crawler — user routes (en)", () => {
    for (const route of USER_ROUTES) {
        test(`user:${route.name}`, async ({ page, createPet, seededActivity }) => {
            const ids: Ids = { petId: (await createPet()).id, activityId: seededActivity.id };
            await crawlRoute(page, localePath(route.path(ids)), route.name);
        });
    }
});

test.describe("Crawler — i18n smoke (fr + ar RTL)", () => {
    const localesToCheck: Locale[] = LOCALES.filter((l) => l !== "en");
    const routes = ["/explore", "/pets", "/favorites", "/profile", "/settings/about"];

    for (const locale of localesToCheck) {
        for (const path of routes) {
            test(`${locale}:${path}`, async ({ page }) => {
                const { consoleErrors, badResponses } = attachErrorCollectors(page);
                await page.goto(localePath(path, locale), { waitUntil: "domcontentloaded" });
                await page.waitForTimeout(1000);
                await expectNoAppError(page);

                if (locale === "ar") {
                    const dir = await page.locator("html").getAttribute("dir");
                    expect(dir).toBe("rtl");
                }

                expect(badResponses, `5xx on ${locale}${path}`).toEqual([]);
                expect(consoleErrors, `console errors on ${locale}${path}`).toEqual([]);
            });
        }
    }
});
