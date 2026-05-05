"use client";

import { ArrowLeft } from "@solar-icons/react";

export function BookingHeader({ title, onBack }: { title: string; onBack: () => void }) {
    return (
        <header className="sticky top-0 z-10 flex items-center gap-3 border-b bg-background px-4 py-3">
            <button
                type="button"
                onClick={onBack}
                aria-label="Back"
                className="flex size-10 items-center justify-center rounded-full hover:bg-muted"
            >
                <ArrowLeft className="size-5" />
            </button>
            <h1 className="text-lg font-semibold">{title}</h1>
        </header>
    );
}
