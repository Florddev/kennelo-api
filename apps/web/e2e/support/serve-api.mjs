import { execFileSync, spawn } from "node:child_process";
import { copyFileSync, existsSync } from "node:fs";
import { resolve, join } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = resolve(fileURLToPath(import.meta.url), "..", "..", "..");
const API_DIR = resolve(ROOT, "..", "..", "api");
const DB_DIR = join(API_DIR, "database");
const E2E_DB_PATH = join(DB_DIR, "database.e2e.sqlite");
const E2E_BASELINE_PATH = join(DB_DIR, "database.e2e.baseline.sqlite");

function step(message) {
    console.log(`\n[35m▸ [e2e][0m ${message}`);
}

function buildFreshDb() {
    execFileSync("php", ["-r", `file_put_contents(${JSON.stringify(E2E_DB_PATH)}, "");`], {
        cwd: API_DIR,
        stdio: "inherit",
    });
    execFileSync("php", ["artisan", "migrate:fresh", "--seed", "--force", "--no-interaction"], {
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
    copyFileSync(E2E_DB_PATH, E2E_BASELINE_PATH);
}

if (!existsSync(E2E_DB_PATH) || process.env.E2E_FORCE_DB_REBUILD === "1") {
    step("Step 1/2 — Building the isolated test database (migrate + seed, ~30s)…");
    buildFreshDb();
    step("Test database ready (baseline snapshot taken).");
} else {
    step(`Reusing existing test database at ${E2E_DB_PATH}`);
}

step("Step 2/2 — Starting the API server on http://localhost:8000 …");

const child = spawn("php", ["artisan", "serve", "--host=127.0.0.1", "--port=8000", "--no-reload"], {
    cwd: API_DIR,
    stdio: "inherit",
    env: { ...process.env, APP_ENV: "local", DB_CONNECTION: "sqlite", DB_DATABASE: E2E_DB_PATH },
});

const forward = (signal) => () => child.kill(signal);
process.on("SIGINT", forward("SIGINT"));
process.on("SIGTERM", forward("SIGTERM"));
child.on("exit", (code) => process.exit(code ?? 0));
