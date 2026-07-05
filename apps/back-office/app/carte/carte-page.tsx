"use client"

import type { CSSProperties } from "react"

import { AppSidebar } from "@/components/layout/app-sidebar"
import { SiteHeader } from "@/components/layout/site-header"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { MapView } from "@/features/map/components/map-view"

export function CartePage() {
  return (
    <SidebarProvider
      style={
        {
          "--sidebar-width": "calc(var(--spacing) * 64)",
          "--header-height": "calc(var(--spacing) * 12)",
        } as CSSProperties
      }
    >
      <AppSidebar variant="inset" />
      <SidebarInset className="ml-0!">
        <SiteHeader title="Carte des pensions" />
        <div className="flex flex-1 flex-col py-4 md:py-6">
          <MapView />
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}
