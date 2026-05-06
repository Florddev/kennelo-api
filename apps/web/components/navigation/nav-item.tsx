"use client";

import Link from "next/link";
import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";
import { IconProps } from "@solar-icons/react";

export interface NavigationItem {
    icon?: React.ComponentType<IconProps>;
    text: string;
    active: boolean;
    href: string;
    special?: boolean;
}

export default function NavItem({
    children,
    href = "#",
    active = false,
    Icon,
    iconSize = 24,
    className,
    classNameIcon,
    onClick,
}: {
    children?: React.ReactNode;
    href?: string;
    active?: boolean;
    Icon?: React.ComponentType<IconProps>;
    iconSize?: number;
    className?: string;
    classNameIcon?: string;
    onClick?: () => void;
}) {
    return (
        <Button
            variant="ghost"
            className={cn(
                "px-1 flex flex-col gap-0 hover:bg-transparent hover:text-primary transition-colors",
                active && "!text-primary",
                className,
            )}
            onClick={onClick}
            asChild
        >
            <Link href={href}>
                {Icon && (
                    <Icon
                        size={iconSize}
                        weight={active ? "BoldDuotone" : "Linear"}
                        className={cn(
                            "transition-colors size-auto",
                            active &&
                                "scale-110 text-primary [&_*[opacity]]:opacity-100 [&_*[opacity]]:text-secondary",
                            classNameIcon,
                        )}
                    />
                )}
                {children}
            </Link>
        </Button>
    );
}
