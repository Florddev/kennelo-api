"use client";

import Link from "next/link";
import Image from "next/image";
import { useTranslations } from "next-intl";
import type { IconProps } from "@solar-icons/react";

import { cn } from "@workspace/ui/lib/utils";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import NavItem from "../nav-item";
import NavButton from "../nav-button";
import UserMenu from "../user-menu";

type HostingNavLink = {
    href: string;
    label: string;
    active: boolean;
    icon?: React.ComponentType<IconProps>;
};

export function HostingNavbar({
    links,
    className,
}: {
    links: HostingNavLink[];
    className?: string;
}) {
    const { user, hasActivity } = useAuth();
    const { routes } = useNavigation();
    const t = useTranslations();

    return (
        <header
            className={cn(
                "sticky top-0 start-0 w-full h-[var(--header-height)] flex items-center z-20 transition-backdrop transition-background duration-150 border-b bg-card/90 backdrop-blur-sm",
                className,
            )}
        >
            <div className="grid grid-cols-[1fr_auto_1fr] items-center w-full px-8 h-full gap-6">
                <Link
                    href={routes.Home()}
                    className="justify-self-start relative h-full flex items-center font-semibold text-lg"
                >
                    <Image
                        className="object-cover max-h-full h-6 w-auto"
                        src="/logo_font.svg"
                        height={120}
                        width={30}
                        alt="Kennelo logo"
                    />
                </Link>

                <nav className="justify-self-center flex items-center gap-6">
                    {links.map((link) => (
                        <NavItem
                            key={link.href}
                            href={link.href}
                            active={link.active}
                            Icon={link.icon}
                            iconSize={28}
                            className="mt-1 flex-row items-center gap-1.5"
                            classNameIcon="text-primary"
                        >
                            {link.label}
                        </NavItem>
                    ))}
                </nav>

                <div className="justify-self-end flex items-center gap-3">
                    <Link href={routes.Home()}>
                        <NavButton variant="ghost">{t("common.actions.switchToClient")}</NavButton>
                    </Link>
                    <UserMenu
                        user={user ?? undefined}
                        hasActivity={hasActivity}
                        className="size-9 shadow-lg"
                    />
                </div>
            </div>
        </header>
    );
}

export type { HostingNavLink };
