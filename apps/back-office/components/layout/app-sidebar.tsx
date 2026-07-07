"use client"

import * as React from "react"
import Link from "next/link"

import { NavMain } from "@/components/layout/nav-main"
import { NavUser } from "@/components/layout/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"
import { Badge } from "@/components/ui/badge"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  Analytics01Icon,
  Building06Icon,
  CommandIcon,
  ContactBookIcon,
  Location01Icon,
  MapsIcon,
  Notebook01Icon,
  SearchList01Icon,
  Settings02Icon,
  UserGroupIcon,
} from "@hugeicons/core-free-icons"
import KenneloIcon from "../svg/kennelo-icon"

const navSections = [
  {
    items: [
      {
        title: "Tableau de bord",
        url: "/dashboard",
        icon: <HugeiconsIcon icon={Analytics01Icon} strokeWidth={2} />,
      },
    ],
  },
  {
    label: "Prospection",
    items: [
      {
        title: "Prospection",
        url: "/dashboard/prospection",
        icon: <HugeiconsIcon icon={Location01Icon} strokeWidth={2} />,
      },
      {
        title: "Carte",
        url: "/dashboard/carte",
        icon: <HugeiconsIcon icon={MapsIcon} strokeWidth={2} />,
      },
      {
        title: "CRM",
        url: "/dashboard/crm",
        icon: <HugeiconsIcon icon={ContactBookIcon} strokeWidth={2} />,
      },
    ],
  },
  {
    label: "Plateforme",
    items: [
      {
        title: "Professionnels",
        url: "/dashboard/professionnels",
        icon: <HugeiconsIcon icon={Building06Icon} strokeWidth={2} />,
      },
      {
        title: "Utilisateurs",
        url: "/dashboard/utilisateurs",
        icon: <HugeiconsIcon icon={UserGroupIcon} strokeWidth={2} />,
      },
      {
        title: "Recherches",
        url: "/dashboard/recherches",
        icon: <HugeiconsIcon icon={SearchList01Icon} strokeWidth={2} />,
      },
    ],
  },
  {
    label: "Système",
    items: [
      {
        title: "Journal",
        url: "/dashboard/journal",
        icon: <HugeiconsIcon icon={Notebook01Icon} strokeWidth={2} />,
      },
      {
        title: "Paramètres",
        url: "/dashboard/parametres",
        icon: <HugeiconsIcon icon={Settings02Icon} strokeWidth={2} />,
      },
    ],
  },
]

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  return (
    <Sidebar collapsible="icon" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              className="data-[slot=sidebar-menu-button]:p-1.5!"
              render={<Link href="/dashboard" />}
            >
              <KenneloIcon className="size-5!" />
              <span className="text-base font-semibold">
                Kennelo
                <Badge variant="outline" className="ml-1.5">
                  Back-office
                </Badge>
              </span>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain sections={navSections} />
      </SidebarContent>
      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  )
}
