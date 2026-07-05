"use client"

import { SiteHeader } from "@/components/layout/site-header"
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
    <>
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
    </>
  )
}
