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
  Location01Icon,
  MapsIcon,
  Notebook01Icon,
  SearchList01Icon,
  UserGroupIcon,
} from "@hugeicons/core-free-icons"

const navMain = [
  {
    title: "Tableau de bord",
    url: "/dashboard",
    icon: <HugeiconsIcon icon={Analytics01Icon} strokeWidth={2} />,
  },
  {
    title: "Professionnels",
    url: "/professionnels",
    icon: <HugeiconsIcon icon={Building06Icon} strokeWidth={2} />,
  },
  {
    title: "Prospection",
    url: "/prospection",
    icon: <HugeiconsIcon icon={Location01Icon} strokeWidth={2} />,
  },
  {
    title: "Carte",
    url: "/carte",
    icon: <HugeiconsIcon icon={MapsIcon} strokeWidth={2} />,
  },
  {
    title: "Recherches",
    url: "/recherches",
    icon: <HugeiconsIcon icon={SearchList01Icon} strokeWidth={2} />,
  },
  {
    title: "Utilisateurs",
    url: "/utilisateurs",
    icon: <HugeiconsIcon icon={UserGroupIcon} strokeWidth={2} />,
  },
  {
    title: "Journal",
    url: "/journal",
    icon: <HugeiconsIcon icon={Notebook01Icon} strokeWidth={2} />,
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
              <HugeiconsIcon
                icon={CommandIcon}
                strokeWidth={2}
                className="size-5!"
              />
              <span className="text-base font-semibold">
                Kennelo
                <Badge variant="outline" className="ml-1.5">
                  Admin
                </Badge>
              </span>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={navMain} />
      </SidebarContent>
      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  )
}
