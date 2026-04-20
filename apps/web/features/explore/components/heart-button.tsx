"use client";

import { useState, MouseEvent } from "react";
import { Heart } from "lucide-react";
import { cn } from "@workspace/ui/lib/utils";

type HeartButtonProps = {
    className?: string;
    size?: number;
    iconSize?: number;
    defaultFavorited?: boolean;
};

export function HeartButton({
    className,
    size = 52,
    iconSize = 26,
    defaultFavorited = false,
}: HeartButtonProps) {
    const [favorited, setFavorited] = useState(defaultFavorited);

    const handleClick = (event: MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        event.stopPropagation();
        setFavorited((current) => !current);
    };

    return (
        <button
            type="button"
            aria-pressed={favorited}
            onClick={handleClick}
            className={cn(
                "flex items-center justify-center rounded-full bg-white shadow-[0px_4px_17px_0px_rgba(0,0,0,0.1)] transition-transform hover:scale-105 active:scale-95",
                className,
            )}
            style={{ width: size, height: size }}
            data-slot="heart-button"
        >
            <Heart
                style={{ width: iconSize, height: iconSize }}
                className={cn(
                    "transition-colors",
                    favorited ? "fill-rose-500 stroke-rose-500" : "fill-rose-400 stroke-rose-400",
                )}
                strokeWidth={2}
            />
        </button>
    );
}
