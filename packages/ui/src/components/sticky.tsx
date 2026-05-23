"use client";

import * as React from "react";

import { cn } from "@workspace/ui/lib/utils";

type StickyProps = React.ComponentProps<"div"> & {
    top?: number;
    stickyClassName?: string;
    onStickyChange?: (isSticky: boolean) => void;
};

function Sticky({
    top = 0,
    stickyClassName,
    onStickyChange,
    className,
    style,
    children,
    ...props
}: StickyProps) {
    const ref = React.useRef<HTMLDivElement>(null);
    const [isSticky, setIsSticky] = React.useState(false);
    const callbackRef = React.useRef(onStickyChange);

    React.useEffect(() => {
        callbackRef.current = onStickyChange;
    }, [onStickyChange]);

    React.useEffect(() => {
        const el = ref.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (!entry) return;
                const stuck = entry.intersectionRect.top === entry.rootBounds?.top;
                setIsSticky(stuck);
                callbackRef.current?.(stuck);
            },
            { rootMargin: `-${top}px 0px 0px 0px`, threshold: [1] },
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [top]);

    return (
        <div
            ref={ref}
            data-slot="sticky"
            data-sticky={isSticky || undefined}
            style={{ top: `${top}px`, ...style }}
            className={cn("sticky", className, isSticky && stickyClassName)}
            {...props}
        >
            {children}
        </div>
    );
}

export { Sticky };
