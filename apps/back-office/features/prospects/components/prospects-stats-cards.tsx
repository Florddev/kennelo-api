"use client"

import { useMemo } from "react"

import {
  PROSPECT_STATUS,
  type ProspectModel,
} from "@workspace/modules/prospects"

import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

export function ProspectsStatsCards({
  prospects,
}: {
  prospects: ProspectModel[]
}) {
  const stats = useMemo(
    () => ({
      total: prospects.length,
      contacted: prospects.filter((prospect) => prospect.isContacted()).length,
      registered: prospects.filter((prospect) => prospect.isRegistered).length,
      pending: prospects.filter(
        (prospect) => prospect.status === PROSPECT_STATUS.NON_CONTACTE
      ).length,
    }),
    [prospects]
  )

  const cards = [
    {
      description: "Prospects chargés",
      value: stats.total,
      footer: "Résultats correspondant aux filtres (max. 100)",
    },
    {
      description: "Contactés",
      value: stats.contacted,
      footer: "Au moins une prise de contact",
    },
    {
      description: "Inscrits sur Kennelo",
      value: stats.registered,
      footer: "Rapprochés à un professionnel inscrit",
    },
    {
      description: "À contacter",
      value: stats.pending,
      footer: "Statut non contacté",
    },
  ]

  return (
    <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-linear-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:bg-card">
      {cards.map((card) => (
        <Card key={card.description} className="@container/card">
          <CardHeader>
            <CardDescription>{card.description}</CardDescription>
            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
              {card.value.toLocaleString("fr-FR")}
            </CardTitle>
          </CardHeader>
          <CardFooter className="text-sm text-muted-foreground">
            {card.footer}
          </CardFooter>
        </Card>
      ))}
    </div>
  )
}
