import { NextResponse } from "next/server";

// Health check endpoint for the container healthcheck.
// Lives under /api so it bypasses the i18n/subdomain middleware
// (see proxy.ts matcher), and therefore returns 200 regardless of
// the Host header or locale.
export const dynamic = "force-dynamic";

export function GET() {
    return NextResponse.json({ status: "ok" }, { status: 200 });
}
