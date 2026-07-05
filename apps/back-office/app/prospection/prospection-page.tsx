"use client"

import type { CSSProperties } from "react"

import { AppSidebar } from "@/components/layout/app-sidebar"
import { SiteHeader } from "@/components/layout/site-header"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { ProspectsStatsCards } from "@/features/prospects/components/prospects-stats-cards"
import { ProspectsTable } from "@/features/prospects/components/prospects-table"
import { useProspects } from "@/features/prospects/hooks/use-prospects"

export function ProspectionPage() {
  const {
    prospects,
    loading,
    error,
    search,
    setSearch,
    status,
    setStatus,
    registered,
    setRegistered,
    refresh,
  } = useProspects()

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
        <SiteHeader title="Prospection" />
        <div className="flex flex-1 flex-col">
          <div className="@container/main flex flex-1 flex-col gap-2">
            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
              <ProspectsStatsCards prospects={prospects} />
              <ProspectsTable
                prospects={prospects}
                loading={loading}
                error={error}
                search={search}
                onSearchChange={setSearch}
                status={status}
                onStatusChange={setStatus}
                registered={registered}
                onRegisteredChange={setRegistered}
                onRefresh={refresh}
              />
            </div>
          </div>
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}
