"use client"

import { SiteHeader } from "@/components/layout/site-header"
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
    <>
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
    </>
  )
}
