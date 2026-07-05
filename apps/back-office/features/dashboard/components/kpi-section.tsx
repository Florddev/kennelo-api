"use client"

import {
  Analytics01Icon,
  Building06Icon,
  Calendar03Icon,
  Location01Icon,
  MoneyBag02Icon,
  Search01Icon,
  UserMultiple02Icon,
} from "@hugeicons/core-free-icons"

import { Separator } from "@/components/ui/separator"
import { Spinner } from "@/components/ui/spinner"
import { BookingsSection } from "@/features/dashboard/components/bookings-section"
import { BusinessKpiCards } from "@/features/dashboard/components/business-kpi-cards"
import { CommunitySection } from "@/features/dashboard/components/community-section"
import { FinanceSection } from "@/features/dashboard/components/finance-section"
import { MarketOpportunities } from "@/features/dashboard/components/market-opportunities"
import { ProspectionFunnel } from "@/features/dashboard/components/prospection-funnel"
import { SearchCharts } from "@/features/dashboard/components/search-charts"
import { SearchQuality } from "@/features/dashboard/components/search-quality"
import { SectionHeading } from "@/features/dashboard/components/section-heading"
import {
  StatCardGrid,
  type StatCardData,
} from "@/features/dashboard/components/stat-card"
import { TeamPerformance } from "@/features/dashboard/components/team-performance"
import { useStats } from "@/features/dashboard/hooks/use-stats"

export function KpiSection() {
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

  const activityCards: StatCardData[] = [
    {
      description: "Professionnels validés",
      value: overview.activities.approved,
      footer: `${overview.activities.total.toLocaleString("fr-FR")} activités au total`,
    },
    {
      description: "En attente de validation",
      value: overview.activities.pending,
      footer: "À traiter dans « Professionnels »",
    },
    {
      description: "Profils refusés",
      value: overview.activities.rejected,
      footer: `${overview.activities.professionals.toLocaleString("fr-FR")} pros avec SIRET`,
    },
  ]

  const prospectCards: StatCardData[] = [
    {
      description: "Prospects identifiés",
      value: overview.prospects.total,
      footer: "Établissements découverts via Apify",
    },
    {
      description: "Déjà inscrits sur Kennelo",
      value: overview.prospects.registered,
      footer: "Rapprochés à un professionnel",
    },
    {
      description: "Inscriptions via prospection",
      value: overview.prospects.from_prospection,
      footer: "Prospects passés au statut « Inscrit »",
    },
  ]

  return (
    <>
      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={Analytics01Icon}
          title="Indicateurs clés"
          description="Les KPI business à suivre pour piloter la croissance."
        />
        <BusinessKpiCards business={business} />
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <ProspectionFunnel funnel={business.prospection_funnel} />
          <MarketOpportunities coverage={business.market_coverage} />
        </div>
      </div>

      <Separator />

      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={MoneyBag02Icon}
          title="Finance"
          description="Chiffre d'affaires, revenu Kennelo et santé économique."
        />
        <FinanceSection finance={finance} />
      </div>

      <Separator />

      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={Calendar03Icon}
          title="Réservations"
          description="Volume, conversion et statuts des réservations."
        />
        <BookingsSection bookings={bookings} />
      </div>

      <Separator />

      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={Building06Icon}
          title="Professionnels"
          description="Validation et suivi des activités inscrites sur la plateforme."
        />
        <StatCardGrid cards={activityCards} columns={3} />
      </div>

      <Separator />

      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={Location01Icon}
          title="Prospection"
          description="Découverte commerciale et conversion des prospects."
        />
        <StatCardGrid cards={prospectCards} columns={3} />
        <TeamPerformance team={business.team_performance} />
      </div>

      <Separator />

      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={UserMultiple02Icon}
          title="Communauté"
          description="Croissance, engagement et satisfaction des utilisateurs."
        />
        <CommunitySection community={community} />
      </div>

      <Separator />

      <div className="flex flex-col gap-4">
        <SectionHeading
          icon={Search01Icon}
          title="Recherches utilisateurs"
          description="Demande des utilisateurs, qualité et comportement de recherche."
        />
        <SearchQuality searches={searches} />
        <SearchCharts searches={searches} />
      </div>
    </>
  )
}
