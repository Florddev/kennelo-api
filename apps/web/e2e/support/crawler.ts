import { expect, type Page, type ConsoleMessage } from "@playwright/test";
import { expectNoAppError } from "./selectors";

const IGNORED_CONSOLE = [
    /Content Security Policy/i,
    /tweakcn/i,
    /accounts\.google\.com/i,
    /Pusher/i,
    /WebSocket/i,
    /Failed to load resource/i,
    /net::ERR/i,
    /Download the React DevTools/i,
    /\[Fast Refresh\]/i,
    /Reverb/i,
    /maplibre/i,
    /basemaps\.cartocdn/i,
    /Failed to fetch/i,
    /document is not defined/i,
    /Switched to client rendering/i,
];

const IGNORED_RESPONSE = [
    /broadcasting\/auth/i,
    /accounts\.google/i,
    /tweakcn/i,
    /\/_next\//i,
    /favicon/i,
    /\/activities\/[^/]+\/bookings/i,
];

export type Collectors = { consoleErrors: string[]; badResponses: string[] };

export function attachErrorCollectors(page: Page): Collectors {
    const consoleErrors: string[] = [];
    const badResponses: string[] = [];

    page.on("console", (msg: ConsoleMessage) => {
        if (msg.type() !== "error") return;
        const text = msg.text();
        if (IGNORED_CONSOLE.some((re) => re.test(text))) return;
        consoleErrors.push(text);
    });
    page.on("pageerror", (err) => {
        if (IGNORED_CONSOLE.some((re) => re.test(err.message))) return;
        consoleErrors.push(`pageerror: ${err.message}`);
    });
    page.on("response", (res) => {
        const url = res.url();
        if (IGNORED_RESPONSE.some((re) => re.test(url))) return;
        if (res.status() >= 500) badResponses.push(`${res.status()} ${url}`);
    });

    return { consoleErrors, badResponses };
}

export async function clickSafeInteractives(page: Page): Promise<void> {
    const tabs = page.getByRole("tab");
    const tabCount = Math.min(await tabs.count(), 6);
    for (let i = 0; i < tabCount; i += 1) {
        await tabs
            .nth(i)
            .click({ timeout: 2000 })
            .catch(() => undefined);
        await page.waitForTimeout(150);
    }
}

export async function crawlRoute(page: Page, url: string, label: string): Promise<void> {
    const { consoleErrors, badResponses } = attachErrorCollectors(page);

    await page.goto(url, { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(1200);
    await expectNoAppError(page);
    await clickSafeInteractives(page);
    await page.waitForTimeout(200);

    expect(badResponses, `5xx responses on ${label}`).toEqual([]);
    expect(consoleErrors, `console errors on ${label}`).toEqual([]);
}
