"use client"

import type { CSSProperties } from "react"

import { AppSidebar } from "@/components/layout/app-sidebar"
import { SiteHeader } from "@/components/layout/site-header"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { CrmStatsCards } from "@/features/crm/components/crm-stats-cards"
import { CrmTable } from "@/features/crm/components/crm-table"
import { useCrmActivities } from "@/features/crm/hooks/use-crm-activities"

export function CrmPage() {
  const {
    activities,
    allActivities,
    loading,
    error,
    search,
    setSearch,
    linked,
    setLinked,
    refresh,
  } = useCrmActivities()

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
        <SiteHeader title="CRM" />
        <div className="flex flex-1 flex-col">
          <div className="@container/main flex flex-1 flex-col gap-2">
            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
              <CrmStatsCards activities={allActivities} />
              <CrmTable
                activities={activities}
                loading={loading}
                error={error}
                search={search}
                onSearchChange={setSearch}
                linked={linked}
                onLinkedChange={setLinked}
                onRefresh={refresh}
              />
            </div>
          </div>
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}
