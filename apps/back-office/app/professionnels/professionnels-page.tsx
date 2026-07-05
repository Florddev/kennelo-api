"use client"

import type { CSSProperties } from "react"

import { AppSidebar } from "@/components/layout/app-sidebar"
import { SiteHeader } from "@/components/layout/site-header"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { ProfessionalsTable } from "@/features/professionals/components/professionals-table"
import { useProfessionals } from "@/features/professionals/hooks/use-professionals"

export function ProfessionnelsPage() {
  const {
    professionals,
    loading,
    error,
    search,
    setSearch,
    status,
    setStatus,
    refresh,
  } = useProfessionals()

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
        <SiteHeader title="Professionnels" />
        <div className="flex flex-1 flex-col">
          <div className="@container/main flex flex-1 flex-col gap-2">
            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
              <ProfessionalsTable
                professionals={professionals}
                loading={loading}
                error={error}
                search={search}
                onSearchChange={setSearch}
                status={status}
                onStatusChange={setStatus}
                onRefresh={refresh}
              />
            </div>
          </div>
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}
