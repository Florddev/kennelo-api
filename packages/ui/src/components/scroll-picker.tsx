"use client";

import * as React from "react";
import { useCallback, useEffect, useLayoutEffect, useRef, useState } from "react";

import { cn } from "@workspace/ui/lib/utils";

export type ScrollPickerItem = {
    label: string;
    value: string | number;
};

export type ScrollPickerSize = "xs" | "sm" | "default" | "lg";

export const ITEM_SIZES: Record<ScrollPickerSize, number> = {
    xs: 36,
    sm: 48,
    default: 56,
    lg: 64,
};

const ITEM_TEXT: Record<ScrollPickerSize, string> = {
    xs: "text-xs",
    sm: "text-sm",
    default: "text-base",
    lg: "text-lg",
};

export type ScrollPickerProps = {
    items: ScrollPickerItem[];
    value: string | number;
    onChange: (value: string | number) => void;
    orientation?: "horizontal" | "vertical";
    size?: ScrollPickerSize;
    itemSize?: number;
    itemWidth?: number;
    sideItems?: number;
    ringClassName?: string;
    className?: string;
};

function ScrollPicker({
    items,
    value,
    onChange,
    orientation = "horizontal",
    size = "sm",
    itemSize: itemSizeProp,
    itemWidth: itemWidthProp,
    sideItems = 2,
    ringClassName,
    className,
}: ScrollPickerProps) {
    const itemH = itemSizeProp ?? ITEM_SIZES[size];
    const itemW = itemWidthProp ?? itemH;

    const isHorizontal = orientation === "horizontal";
    const scrollUnit = isHorizontal ? itemW : itemH;

    const scrollRef = useRef<HTMLDivElement>(null);
    const isUserScrollingRef = useRef(false);
    const commitTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const selectedIndex = items.findIndex((i) => i.value === value);
    const initialIndex = selectedIndex >= 0 ? selectedIndex : 0;

    const [scrollPos, setScrollPos] = useState(() => initialIndex * scrollUnit);

    const scrollToIndex = useCallback(
        (index: number, behavior: ScrollBehavior = "smooth") => {
            const el = scrollRef.current;
            if (!el) return;
            const target = Math.max(0, index) * scrollUnit;
            if (behavior === "instant") {
                if (isHorizontal) el.scrollLeft = target;
                else el.scrollTop = target;
            } else {
                if (isHorizontal) el.scrollTo({ left: target, behavior });
                else el.scrollTo({ top: target, behavior });
            }
        },
        [isHorizontal, scrollUnit],
    );

    useLayoutEffect(() => {
        scrollToIndex(initialIndex, "instant");
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (isUserScrollingRef.current) return;
        const index = selectedIndex >= 0 ? selectedIndex : 0;
        scrollToIndex(index);
    }, [value, selectedIndex, scrollToIndex]);

    const commitScrolledValue = useCallback(() => {
        const el = scrollRef.current;
        if (!el) return;
        const pos = isHorizontal ? el.scrollLeft : el.scrollTop;
        const index = Math.round(pos / scrollUnit);
        const clamped = Math.max(0, Math.min(index, items.length - 1));
        scrollToIndex(clamped);
        const item = items[clamped];
        if (item && item.value !== value) onChange(item.value);
        isUserScrollingRef.current = false;
    }, [isHorizontal, scrollUnit, items, value, onChange, scrollToIndex]);

    const handleScroll = useCallback(
        (e: React.UIEvent<HTMLDivElement>) => {
            isUserScrollingRef.current = true;
            const pos = isHorizontal ? e.currentTarget.scrollLeft : e.currentTarget.scrollTop;
            setScrollPos(pos);
            if (commitTimeoutRef.current) clearTimeout(commitTimeoutRef.current);
            commitTimeoutRef.current = setTimeout(commitScrolledValue, 150);
        },
        [isHorizontal, commitScrolledValue],
    );

    useEffect(() => {
        return () => {
            if (commitTimeoutRef.current) clearTimeout(commitTimeoutRef.current);
        };
    }, []);

    const centerIndex = scrollPos / scrollUnit;

    const containerStyle: React.CSSProperties = isHorizontal
        ? { width: itemW * (sideItems * 2 + 1), height: itemH }
        : { height: itemH * (sideItems * 2 + 1), width: itemW };

    const scrollContainerStyle: React.CSSProperties = {
        scrollSnapType: isHorizontal ? "x mandatory" : "y mandatory",
        scrollbarWidth: "none",
        paddingInlineStart: isHorizontal ? itemW * sideItems : undefined,
        paddingInlineEnd: isHorizontal ? itemW * sideItems : undefined,
        paddingBlockStart: !isHorizontal ? itemH * sideItems : undefined,
        paddingBlockEnd: !isHorizontal ? itemH * sideItems : undefined,
    };

    return (
        <div
            data-slot="scroll-picker"
            data-size={size}
            className={cn("relative shrink-0", className)}
            style={containerStyle}
        >
            <div
                aria-hidden
                className={cn(
                    "pointer-events-none absolute inset-0 m-auto rounded-xl border-2 border-border z-10",
                    ringClassName,
                )}
                style={{ width: itemW, height: itemH }}
            />
            <div
                ref={scrollRef}
                tabIndex={-1}
                onScroll={handleScroll}
                style={scrollContainerStyle}
                className={cn(
                    "absolute inset-0 flex [&::-webkit-scrollbar]:hidden",
                    isHorizontal ? "flex-row overflow-x-scroll" : "flex-col overflow-y-scroll",
                )}
            >
                {items.map((item, index) => {
                    const distance = Math.abs(index - centerIndex);
                    const opacity = Math.max(0.2, 1 - distance * 0.35);
                    const scale = Math.max(0.78, 1 - distance * 0.08);

                    return (
                        <button
                            key={item.value}
                            type="button"
                            data-slot="scroll-picker-item"
                            data-selected={index === Math.round(centerIndex)}
                            style={{
                                scrollSnapAlign: "center",
                                width: itemW,
                                height: itemH,
                                flexShrink: 0,
                                opacity,
                                transform: `scale(${scale})`,
                                transition: "opacity 0.1s, transform 0.1s",
                            }}
                            className={cn(
                                "flex items-center justify-center font-semibold select-none px-1 overflow-hidden",
                                ITEM_TEXT[size],
                            )}
                            onClick={() => {
                                scrollToIndex(index);
                                onChange(item.value);
                            }}
                        >
                            {item.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export { ScrollPicker };
