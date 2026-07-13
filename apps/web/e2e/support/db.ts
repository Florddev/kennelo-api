import { execFileSync } from "node:child_process";
import { copyFileSync, existsSync, rmSync } from "node:fs";
import { join, resolve } from "node:path";

const API_DIR = resolve(process.cwd(), "..", "..", "api");
const DB_DIR = join(API_DIR, "database");

export const E2E_DB_PATH = join(DB_DIR, "database.e2e.sqlite");
export const E2E_BASELINE_PATH = join(DB_DIR, "database.e2e.baseline.sqlite");

export function apiDir(): string {
    return API_DIR;
}

function runArtisan(args: string[]): void {
    execFileSync("php", ["artisan", ...args], {
        cwd: API_DIR,
        stdio: "inherit",
        env: {
            ...process.env,
            APP_ENV: "local",
            DB_CONNECTION: "sqlite",
            DB_DATABASE: E2E_DB_PATH,
            SEED_REMOTE_IMAGES: "0",
        },
    });
}

export function buildFreshDb(): void {
    removeDb();
    if (!existsSync(DB_DIR)) {
        throw new Error(`Database directory missing: ${DB_DIR}`);
    }
    execFileSync("php", ["-r", `file_put_contents(${JSON.stringify(E2E_DB_PATH)}, "");`], {
        cwd: API_DIR,
        stdio: "inherit",
    });
    runArtisan(["migrate:fresh", "--seed", "--force", "--no-interaction"]);
    copyFileSync(E2E_DB_PATH, E2E_BASELINE_PATH);
}

export function restoreDb(): void {
    if (!existsSync(E2E_BASELINE_PATH)) {
        throw new Error(`No baseline snapshot at ${E2E_BASELINE_PATH}; run buildFreshDb() first.`);
    }
    copyFileSync(E2E_BASELINE_PATH, E2E_DB_PATH);
}

export function removeDb(): void {
    for (const suffix of ["", "-wal", "-shm"]) {
        for (const path of [E2E_DB_PATH, E2E_BASELINE_PATH]) {
            const target = `${path}${suffix}`;
            if (existsSync(target)) {
                rmSync(target, { force: true });
            }
        }
    }
}

export function e2eDbExists(): boolean {
    return existsSync(E2E_DB_PATH);
}

export function verifyUserEmail(email: string): void {
    const escaped = email.replace(/\\/g, "\\\\").replace(/'/g, "\\'");
    runArtisan([
        "tinker",
        `--execute=App\\Models\\User::where('email', '${escaped}')->first()?->markEmailAsVerified();`,
    ]);
}
