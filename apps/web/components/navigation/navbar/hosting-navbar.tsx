"use client";

import Link from "next/link";
import Image from "next/image";
import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";

import { useScrolled } from "@/hooks/use-scrolled";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import NavButton from "../nav-button";
import UserMenu from "../user-menu";

type HostingNavLink = {
    href: string;
    label: string;
    active: boolean;
};

function HostingNavLink({ href, label, active }: HostingNavLink) {
    return (
        <Link
            href={href}
            className={cn(
                "relative inline-flex items-center px-1 py-2 text-sm font-medium transition-colors",
                active ? "text-foreground" : "text-muted-foreground hover:text-foreground",
            )}
        >
            <span>{label}</span>
            <span
                className={cn(
                    "absolute -bottom-0.5 start-0 end-0 h-[2px] rounded-full bg-foreground transition-opacity",
                    active ? "opacity-100" : "opacity-0",
                )}
            />
        </Link>
    );
}

export function HostingNavbar({
    links,
    className,
}: {
    links: HostingNavLink[];
    className?: string;
}) {
    const { user, hasEstablishment } = useAuth();
    const { routes } = useNavigation();
    const scrolled = useScrolled(0);
    const t = useTranslations();

    return (
        <header
            className={cn(
                "sticky top-0 start-0 w-full h-[var(--header-height)] flex items-center z-20 transition-backdrop transition-background duration-150",
                scrolled
                    ? "bg-card/90 backdrop-blur-sm border-border/50 border-b"
                    : "border-b border-transparent",
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

                <nav className="justify-self-center flex items-center gap-8">
                    {links.map((link) => (
                        <HostingNavLink
                            key={link.href}
                            href={link.href}
                            label={link.label}
                            active={link.active}
                        />
                    ))}
                </nav>

                <div className="justify-self-end flex items-center gap-3">
                    <Link href={routes.Home()}>
                        <NavButton variant="ghost">{t("common.actions.switchToClient")}</NavButton>
                    </Link>
                    <UserMenu
                        user={user ?? undefined}
                        hasEstablishment={hasEstablishment}
                        className="size-9 shadow-lg"
                    />
                </div>
            </div>
        </header>
    );
}

export type { HostingNavLink };
