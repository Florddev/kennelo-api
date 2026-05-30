"use client";

import {
    Building2,
    CalendarClock,
    CalendarDays,
    ChevronRight,
    ChevronsUpDown,
    CreditCard,
    PawPrint,
    ReceiptText,
    Users,
} from "lucide-react";
import { useTranslations } from "next-intl";
import Link from "next/link";
import { usePathname } from "next/navigation";

import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@workspace/ui/components/breadcrumb";
import { Separator } from "@workspace/ui/components/separator";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from "@workspace/ui/components/sidebar";

import { useAuth } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useNavigation } from "@/hooks/use-navigation";
import { useEstablishment } from "../hooks/use-establishment";

type EstablishmentSidebarLayoutProps = {
    establishmentId: string;
    children: React.ReactNode;
};

function normalizePath(path: string): string {
    return path.endsWith("/") ? path.slice(0, -1) : path;
}

export function EstablishmentSidebarLayout({
    establishmentId,
    children,
}: EstablishmentSidebarLayoutProps) {
    const t = useTranslations();
    const pathname = usePathname();
    const { routes } = useNavigation();
    const { user } = useAuth();
    const { establishment } = useEstablishment(establishmentId);

    const infoHref = routes.EstablishmentDetail({ id: establishmentId });

    const configurationItems = [
        {
            href: infoHref,
            label: t("features.my-establishments.manager.nav.info"),
            icon: Building2,
            exact: true,
        },
        {
            href: routes.EstablishmentCapacities({ id: establishmentId }),
            label: t("features.my-establishments.manager.nav.capacities"),
            icon: PawPrint,
            exact: false,
        },
        {
            href: routes.EstablishmentAvailabilities({ id: establishmentId }),
            label: t("features.my-establishments.manager.nav.availabilities"),
            icon: CalendarClock,
            exact: false,
        },
    ];

    const activityItems = [
        {
            href: routes.EstablishmentBookings({ id: establishmentId }),
            label: t("features.my-establishments.manager.nav.bookings"),
            icon: CalendarDays,
            exact: false,
        },
        {
            href: routes.EstablishmentCollaborators({ id: establishmentId }),
            label: t("features.my-establishments.manager.nav.collaborators"),
            icon: Users,
            exact: false,
        },
        {
            href: routes.EstablishmentInvoices({ id: establishmentId }),
            label: t("features.my-establishments.manager.nav.invoices"),
            icon: ReceiptText,
            exact: false,
        },
        {
            href: routes.EstablishmentPayment({ id: establishmentId }),
            label: t("features.my-establishments.manager.nav.payment"),
            icon: CreditCard,
            exact: false,
        },
    ];

    const current = normalizePath(pathname);
    const isActive = (href: string, exact: boolean) => {
        const target = normalizePath(href);
        return exact ? current === target : current === target || current.startsWith(`${target}/`);
    };

    const renderItems = (groupItems: typeof configurationItems) =>
        groupItems.map((item) => (
            <SidebarMenuItem key={item.href}>
                <SidebarMenuButton
                    asChild
                    isActive={isActive(item.href, item.exact)}
                    tooltip={item.label}
                    className="font-medium [--radius:0.5rem] data-active:bg-secondary/20 data-active:text-sidebar-foreground"
                >
                    <Link href={item.href}>
                        <item.icon className="size-[15px]!" />
                        <span>{item.label}</span>
                        <ChevronRight className="ms-auto rtl:rotate-180" />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        ));

    const activeItem = [...configurationItems, ...activityItems].find((item) =>
        isActive(item.href, item.exact),
    );
    const avatarUrl = establishment?.getAvatarUrl();

    return (
        <SidebarProvider className="min-h-[calc(100dvh-var(--header-height))]">
            <Sidebar className="top-[var(--header-height)] h-[calc(100svh-var(--header-height))] border-t border-border [&_[data-slot=sidebar-inner]]:relative [&_[data-slot=sidebar-inner]]:overflow-hidden [&_[data-slot=sidebar-inner]]:bg-secondary/20">
                <span
                    aria-hidden
                    className="pointer-events-none absolute -top-8 -start-8 z-0 h-32 w-36 rounded-[50%] bg-secondary"
                />
                <SidebarHeader className="relative h-12 justify-center py-0">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild className="h-10">
                                <Link href={infoHref}>
                                    <Avatar className="size-8 rounded-lg after:rounded-lg [--radius:0.5rem]">
                                        <AvatarImage
                                            src={avatarUrl}
                                            alt={establishment?.name ?? ""}
                                            className="rounded-lg"
                                        />
                                        <AvatarFallback className="rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                            <Building2 className="size-4" />
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="grid flex-1 text-start text-sm leading-tight">
                                        <span className="truncate font-medium">
                                            {establishment?.name}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {t("ui.navigation.hosting.establishment")}
                                        </span>
                                    </div>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <SidebarContent className="relative">
                    <SidebarGroup>
                        <SidebarGroupLabel>
                            {t("features.my-establishments.manager.groups.configuration")}
                        </SidebarGroupLabel>
                        <SidebarMenu>{renderItems(configurationItems)}</SidebarMenu>
                    </SidebarGroup>
                    <SidebarGroup>
                        <SidebarGroupLabel>
                            {t("features.my-establishments.manager.groups.activity")}
                        </SidebarGroupLabel>
                        <SidebarMenu>{renderItems(activityItems)}</SidebarMenu>
                    </SidebarGroup>
                </SidebarContent>

                <SidebarFooter className="relative">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg">
                                <UserAvatar user={user ?? undefined} className="size-8" size="sm" />
                                <div className="grid flex-1 text-start text-sm leading-tight">
                                    <span className="truncate font-medium">
                                        {user?.getFullName()}
                                    </span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {user?.email}
                                    </span>
                                </div>
                                <ChevronsUpDown className="ms-auto size-4" />
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarFooter>

                <SidebarRail />
            </Sidebar>

            <SidebarInset className="bg-transparent border-t border-border">
                <header className="flex h-12 shrink-0 items-center gap-2 border-b">
                    <div className="flex items-center gap-2 px-4">
                        <SidebarTrigger className="-ms-1" />
                        <Separator orientation="vertical" className="me-2 h-4" />
                        <Breadcrumb>
                            <BreadcrumbList>
                                <BreadcrumbItem className="hidden md:block">
                                    <BreadcrumbLink asChild>
                                        <Link href={infoHref}>{establishment?.name}</Link>
                                    </BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator className="hidden md:block" />
                                <BreadcrumbItem>
                                    <BreadcrumbPage>{activeItem?.label}</BreadcrumbPage>
                                </BreadcrumbItem>
                            </BreadcrumbList>
                        </Breadcrumb>
                    </div>
                </header>
                <div className="flex flex-1 flex-col gap-4 p-4">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    );
}
