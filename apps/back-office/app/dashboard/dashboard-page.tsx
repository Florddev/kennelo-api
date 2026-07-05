"use client"

import { SiteHeader } from "@/components/layout/site-header"
import { AnalyticsTabs } from "@/features/dashboard/components/analytics-tabs"

export function DashboardPage() {
  return (
    <>
      <SiteHeader title="Tableau de bord" />
      <div className="flex flex-1 flex-col">
        <div className="@container/main flex flex-1 flex-col gap-2">
          <div className="flex flex-col gap-6 py-4 md:py-6">
            <AnalyticsTabs />
          </div>
        </div>
      </div>
    </>
  )
}
