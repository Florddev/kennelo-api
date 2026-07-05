"use client"

import { useMemo } from "react"

import { type ProfessionalModel } from "@workspace/modules/professionals"

import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

export function CrmStatsCards({
  activities,
}: {
  activities: ProfessionalModel[]
}) {
  const stats = useMemo(() => {
    const total = activities.length
    const linked = activities.filter(
      (activity) => activity.isGoogleLinked
    ).length
    return { total, linked, unlinked: total - linked }
  }, [activities])

  const cards = [
    {
      description: "Activités inscrites",
      value: stats.total,
      footer: "Professionnels validés",
    },
    {
      description: "Liées à Google",
      value: stats.linked,
      footer: "Fiche Google rattachée",
    },
    {
      description: "À lier",
      value: stats.unlinked,
      footer: "Pas encore de fiche Google",
    },
  ]

  return (
    <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-linear-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-3">
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
