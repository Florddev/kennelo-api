"use client";

import { ArrowLeft } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";

export function BookingHeader({ title, onBack }: { title: string; onBack: () => void }) {
    return (
        <header className="flex flex-col gap-2 p-3 pb-0">
            <Button onClick={onBack} variant="flat" size="icon">
                <ArrowLeft className="size-5" />
            </Button>
            <h1 className="text-2xl font-semibold px-1">{title}</h1>
        </header>
    );
}
