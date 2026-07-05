"use client"

import {
  Analytics01Icon,
  Calendar03Icon,
  Location01Icon,
  MoneyBag02Icon,
  Search01Icon,
  UserMultiple02Icon,
} from "@hugeicons/core-free-icons"
import { HugeiconsIcon } from "@hugeicons/react"

import { Spinner } from "@/components/ui/spinner"
import { Tabs, TabsList, TabsPanel, TabsTab } from "@/components/ui/tabs"
import { BookingsSection } from "@/features/dashboard/components/bookings-section"
import { CommunitySection } from "@/features/dashboard/components/community-section"
import { FinanceSection } from "@/features/dashboard/components/finance-section"
import { OverviewPanel } from "@/features/dashboard/components/overview-panel"
import { ProspectionPanel } from "@/features/dashboard/components/prospection-panel"
import { SearchCharts } from "@/features/dashboard/components/search-charts"
import { SearchQuality } from "@/features/dashboard/components/search-quality"
import { SectionHeading } from "@/features/dashboard/components/section-heading"
import { useStats } from "@/features/dashboard/hooks/use-stats"

const tabs = [
  { value: "overview", label: "Vue d'ensemble", icon: Analytics01Icon },
  { value: "finance", label: "Finance", icon: MoneyBag02Icon },
  { value: "bookings", label: "Réservations", icon: Calendar03Icon },
  { value: "community", label: "Communauté", icon: UserMultiple02Icon },
  { value: "searches", label: "Recherches", icon: Search01Icon },
  { value: "prospection", label: "Prospection", icon: Location01Icon },
]

export function AnalyticsTabs() {
  const {
    overview,
    searches,
    business,
    finance,
    bookings,
    community,
    loading,
    error,
  } = useStats()

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="size-6 text-muted-foreground" />
      </div>
    )
  }

  if (
    error ||
    !overview ||
    !searches ||
    !business ||
    !finance ||
    !bookings ||
    !community
  ) {
    return (
      <div className="px-4 py-8 text-center text-sm text-muted-foreground lg:px-6">
        Impossible de charger les statistiques. Vérifiez que l&apos;API est
        démarrée.
      </div>
    )
  }

  return (
    <Tabs defaultValue="overview" className="gap-6">
      <div className="overflow-x-auto px-4 lg:px-6">
        <TabsList>
          {tabs.map((tab) => (
            <TabsTab key={tab.value} value={tab.value}>
              <HugeiconsIcon icon={tab.icon} strokeWidth={2} />
              {tab.label}
            </TabsTab>
          ))}
        </TabsList>
      </div>

      <TabsPanel value="overview">
        <OverviewPanel
          business={business}
          finance={finance}
          bookings={bookings}
          community={community}
        />
      </TabsPanel>

      <TabsPanel value="finance">
        <div className="flex flex-col gap-4">
          <SectionHeading
            icon={MoneyBag02Icon}
            title="Finance"
            description="Chiffre d'affaires, revenu Kennelo et santé économique."
          />
          <FinanceSection finance={finance} />
        </div>
      </TabsPanel>

      <TabsPanel value="bookings">
        <div className="flex flex-col gap-4">
          <SectionHeading
            icon={Calendar03Icon}
            title="Réservations"
            description="Volume, conversion et statuts des réservations."
          />
          <BookingsSection bookings={bookings} />
        </div>
      </TabsPanel>

      <TabsPanel value="community">
        <div className="flex flex-col gap-4">
          <SectionHeading
            icon={UserMultiple02Icon}
            title="Communauté"
            description="Croissance, engagement et satisfaction des utilisateurs."
          />
          <CommunitySection community={community} />
        </div>
      </TabsPanel>

      <TabsPanel value="searches">
        <div className="flex flex-col gap-4">
          <SectionHeading
            icon={Search01Icon}
            title="Recherches utilisateurs"
            description="Demande des utilisateurs, qualité et comportement de recherche."
          />
          <SearchQuality searches={searches} />
          <SearchCharts searches={searches} />
        </div>
      </TabsPanel>

      <TabsPanel value="prospection">
        <ProspectionPanel overview={overview} business={business} />
      </TabsPanel>
    </Tabs>
  )
}
