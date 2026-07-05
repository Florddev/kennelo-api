"use client"

import type { CSSProperties } from "react"

import { AppSidebar } from "@/components/layout/app-sidebar"
import { SiteHeader } from "@/components/layout/site-header"
import { UsersStatsCards } from "@/features/users/components/users-stats-cards"
import { UsersTable } from "@/features/users/components/users-table"
import { useAdminUsers } from "@/features/users/hooks/use-admin-users"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"

export function UtilisateursPage() {
  const { users, loading, error, search, setSearch, role, setRole, refresh } =
    useAdminUsers()

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
        <SiteHeader title="Utilisateurs" />
        <div className="flex flex-1 flex-col">
          <div className="@container/main flex flex-1 flex-col gap-2">
            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
              <UsersStatsCards users={users} />
              <UsersTable
                users={users}
                loading={loading}
                error={error}
                search={search}
                onSearchChange={setSearch}
                role={role}
                onRoleChange={setRole}
                onRefresh={refresh}
              />
            </div>
          </div>
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}
