import { removeDb } from "./support/db";

export default function globalTeardown(): void {
    if (process.env.E2E_KEEP_DB === "1") {
        console.log("[e2e] E2E_KEEP_DB=1 set, leaving test database in place");
        return;
    }
    console.log("[e2e] removing test database (zero-trace teardown)");
    removeDb();
}
