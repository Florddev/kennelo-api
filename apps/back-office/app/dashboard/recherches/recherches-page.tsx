"use client"

import { SiteHeader } from "@/components/layout/site-header"
import { SearchLogsTable } from "@/features/search-logs/components/search-logs-table"
import { useSearchLogs } from "@/features/search-logs/hooks/use-search-logs"

export function RecherchesPage() {
  const {
    logs,
    loading,
    error,
    search,
    setSearch,
    department,
    setDepartment,
    refresh,
  } = useSearchLogs()

  return (
    <>
      <SiteHeader title="Recherches utilisateurs" />
      <div className="flex flex-1 flex-col">
        <div className="@container/main flex flex-1 flex-col gap-2">
          <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
            <SearchLogsTable
              logs={logs}
              loading={loading}
              error={error}
              search={search}
              onSearchChange={setSearch}
              department={department}
              onDepartmentChange={setDepartment}
              onRefresh={refresh}
            />
          </div>
        </div>
      </div>
    </>
  )
}
