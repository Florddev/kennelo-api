export default function globalSetup(): void {
    const line = "─".repeat(64);
    console.log(`\n\x1b[36m${line}\x1b[0m`);
    console.log("\x1b[36m▸ [e2e] Global setup\x1b[0m");
    console.log("  The isolated test database and both servers are started by the");
    console.log("  webServer bootstrap (API: build DB + serve, Web: Next.js dev).");
    console.log("  First run compiles Next.js — this can take 1-2 min with no output.");
    console.log(`\x1b[36m${line}\x1b[0m\n`);
}
