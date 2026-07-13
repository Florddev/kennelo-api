import { defineConfig, devices, type PlaywrightTestConfig } from "@playwright/test";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";
import { existsSync, readFileSync } from "node:fs";

const ROOT_DIR = dirname(fileURLToPath(import.meta.url));

function readEnvValue(key: string): string | undefined {
    if (process.env[key]) return process.env[key];
    for (const envFile of [resolve(ROOT_DIR, ".env.local"), resolve(ROOT_DIR, ".env")]) {
        if (!existsSync(envFile)) continue;
        for (const line of readFileSync(envFile, "utf8").split(/\r?\n/)) {
            const match = line.match(new RegExp(`^\\s*${key}\\s*=\\s*(.*)$`));
            if (match && match[1] !== undefined) return match[1].trim().replace(/^["']|["']$/g, "");
        }
    }
    return undefined;
}

const STRIPE_PUBLISHABLE_KEY = readEnvValue("NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY");
if (STRIPE_PUBLISHABLE_KEY) process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY = STRIPE_PUBLISHABLE_KEY;

const API_DIR = resolve(ROOT_DIR, "..", "..", "api");
const E2E_DB_PATH = resolve(API_DIR, "database", "database.e2e.sqlite");
const API_URL = "http://localhost:8000/api";
const WEB_URL = "http://localhost:3000";

const EXTERNAL_SERVERS = process.env.E2E_EXTERNAL_SERVERS === "1";

const webServer: PlaywrightTestConfig["webServer"] = EXTERNAL_SERVERS
    ? undefined
    : [
          {
              command: `node ${JSON.stringify(resolve(ROOT_DIR, "e2e", "support", "serve-api.mjs"))}`,
              cwd: ROOT_DIR,
              url: `${API_URL}/animal-types`,
              reuseExistingServer: !process.env.CI,
              timeout: 180000,
              env: {
                  APP_ENV: "local",
                  DB_CONNECTION: "sqlite",
                  DB_DATABASE: E2E_DB_PATH,
              },
          },
          {
              command: `pnpm --filter @workspace/translations build && pnpm --filter web dev`,
              cwd: resolve(ROOT_DIR, "..", ".."),
              url: `${WEB_URL}/en/login`,
              reuseExistingServer: !process.env.CI,
              timeout: 240000,
              env: {
                  NEXT_PUBLIC_API_URL: API_URL,
                  NEXT_PUBLIC_ROUTE_MODE: "dynamic",
                  NEXT_PUBLIC_PLATFORM: "web",
                  NEXT_PUBLIC_REVERB_APP_KEY: "e2e-key",
                  NEXT_PUBLIC_REVERB_HOST: "localhost",
                  NEXT_PUBLIC_REVERB_PORT: "8080",
                  NEXT_PUBLIC_REVERB_SCHEME: "http",
                  ...(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY
                      ? {
                            NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY:
                                process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY,
                        }
                      : {}),
              },
          },
      ];

export default defineConfig({
    testDir: "./e2e",
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 1,
    workers: process.env.CI ? 1 : 2,
    timeout: 60000,
    reporter: [["list"], ["html", { open: "never" }]],
    globalSetup: "./e2e/global-setup.ts",
    globalTeardown: "./e2e/global-teardown.ts",
    use: {
        baseURL: `${WEB_URL}/en`,
        trace: "on-first-retry",
        screenshot: "only-on-failure",
    },
    webServer,
    projects: [
        {
            name: "setup:user",
            testMatch: /auth\.fixture\.ts/,
        },
        {
            name: "setup:manager",
            testMatch: /manager\.fixture\.ts/,
        },
        {
            name: "chromium",
            use: {
                ...devices["Desktop Chrome"],
                storageState: "e2e/fixtures/.auth/user.json",
            },
            dependencies: ["setup:user", "setup:manager"],
            testIgnore: [
                /host\/.*\.spec\.ts/,
                /crawler-host\.spec\.ts/,
                /mobile-navigation\.spec\.ts/,
            ],
        },
        {
            name: "chromium:host",
            use: {
                ...devices["Desktop Chrome"],
                storageState: "e2e/fixtures/.auth/manager.json",
            },
            dependencies: ["setup:user", "setup:manager"],
            testMatch: [/host\/.*\.spec\.ts/, /crawler-host\.spec\.ts/],
        },
        {
            name: "mobile",
            use: {
                ...devices["Desktop Chrome"],
                viewport: { width: 375, height: 812 },
                storageState: "e2e/fixtures/.auth/user.json",
            },
            dependencies: ["setup:user"],
            testMatch: /mobile-navigation\.spec\.ts/,
        },
    ],
});
