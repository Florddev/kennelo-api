"use client";

import { type ReactNode, useState } from "react";

import { AltArrowRight } from "@solar-icons/react";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { cn } from "@workspace/ui/lib/utils";

import { rowCn } from "@/components/forms/inline-inputs/shared";

type HostBookingFieldProps = {
    value?: string | null;
    placeholder?: string;
    children: ReactNode;
    contentClassName?: string;
};

export function HostBookingField({
    value,
    placeholder,
    children,
    contentClassName,
}: HostBookingFieldProps) {
    const [open, setOpen] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    data-slot="host-booking-field"
                    className={rowCn(false, false, "w-full text-start", true)}
                >
                    <span className={cn("truncate", !value && "text-muted-foreground")}>
                        {value || placeholder}
                    </span>
                    <AltArrowRight className="size-3.5 shrink-0" />
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" className={cn("p-0", contentClassName)}>
                {children}
            </PopoverContent>
        </Popover>
    );
}
