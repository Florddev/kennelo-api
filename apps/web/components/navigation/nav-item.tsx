"use client";

import { useState } from "react";
import Link from "next/link";
import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";
import type { KIcon } from "@workspace/ui/components/icons";

export interface NavigationItem {
    icon: KIcon;
    text: string;
    active: boolean;
    href: string;
    special?: boolean;
}

interface NavItemProps {
    children?: React.ReactNode;
    href?: string;
    active?: boolean;
    Icon?: KIcon;
    iconSize?: number;
    iconSecondaryOpacity?: number;
    fillIconOnHover?: boolean;
    className?: string;
    onClick?: () => void;
}

function useNavItemState(active: boolean, fillIconOnHover: boolean) {
    const [isHovered, setIsHovered] = useState(false);
    const isFilled = active || (fillIconOnHover && isHovered);
    const secondaryClass = active ? "text-secondary" : "";

    return { isHovered, setIsHovered, isFilled, secondaryClass };
}

export default function NavItem(props: NavItemProps) {
    const {
        children,
        href = "#",
        active = false,
        Icon,
        iconSize = 24,
        iconSecondaryOpacity = 0.4,
        fillIconOnHover = false,
        className,
        onClick,
    } = props;

    const { setIsHovered, isFilled, secondaryClass } = useNavItemState(active, fillIconOnHover);

    return (
        <Button
            variant="ghost"
            className={cn(
                "px-1 flex flex-col gap-0 hover:bg-transparent hover:text-primary transition-colors",
                active && "text-primary",
                className,
            )}
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
            onClick={onClick}
            asChild
        >
            <Link href={href}>
                {Icon && (
                    <Icon
                        size={iconSize}
                        filled={isFilled}
                        secondary={secondaryClass}
                        secondaryOpacity={iconSecondaryOpacity}
                        className={cn("transition-colors size-auto")}
                    />
                )}
                {children}
            </Link>
        </Button>
    );
}
