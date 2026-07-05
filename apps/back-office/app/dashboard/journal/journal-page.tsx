"use client"

import { SiteHeader } from "@/components/layout/site-header"
import { RecentActivity } from "@/features/audit/components/recent-activity"

export function JournalPage() {
  return (
    <>
      <SiteHeader title="Journal d'activité" />
      <div className="flex flex-1 flex-col">
        <div className="@container/main flex flex-1 flex-col gap-2">
          <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
            <RecentActivity />
          </div>
        </div>
      </div>
    </>
  )
}
