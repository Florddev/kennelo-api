"use client"

import { SiteHeader } from "@/components/layout/site-header"
import { UsersStatsCards } from "@/features/users/components/users-stats-cards"
import { UsersTable } from "@/features/users/components/users-table"
import { useAdminUsers } from "@/features/users/hooks/use-admin-users"

export function UtilisateursPage() {
  const { users, loading, error, search, setSearch, role, setRole, refresh } =
    useAdminUsers()

  return (
    <>
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
    </>
  )
}
