"use client"

import { Building06Icon, Location01Icon } from "@hugeicons/core-free-icons"

import type {
  StatsBusinessDto,
  StatsOverviewDto,
} from "@workspace/modules/stats"

import { Separator } from "@/components/ui/separator"
import { SectionHeading } from "@/features/dashboard/components/section-heading"
import {
  StatCardGrid,
  type StatCardData,
} from "@/features/dashboard/components/stat-card"
import { TeamPerformance } from "@/features/dashboard/components/team-performance"

export function ProspectionPanel({
  overview,
  business,
}: {
  overview: StatsOverviewDto
  business: StatsBusinessDto
}) {
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
    <div className="flex flex-col gap-6">
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
    </div>
  )
}
