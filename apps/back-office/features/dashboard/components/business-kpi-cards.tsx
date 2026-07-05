"use client"

import type { StatsBusinessDto } from "@workspace/modules/stats"

import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { CountUp } from "@/features/dashboard/components/count-up"
import { TrendBadge } from "@/features/dashboard/components/trend-badge"

export function BusinessKpiCards({ business }: { business: StatsBusinessDto }) {
  const funnel = business.prospection_funnel
  const validation = business.validation
  const registrations = business.growth.registrations

  const cards = [
    {
      description: "Taux de conversion prospection",
      main: (
        <CountUp
          value={funnel.overall_conversion_rate}
          decimals={1}
          suffix=" %"
        />
      ),
      footer: `${funnel.registered} inscrits sur ${funnel.identified} prospects identifiés`,
      trend: null,
    },
    {
      description: "Taux de validation des pros",
      main: (
        <CountUp value={validation.approval_rate} decimals={1} suffix=" %" />
      ),
      footer:
        validation.avg_processing_days !== null
          ? `Traitement moyen : ${validation.avg_processing_days} j · ${validation.pending} en attente`
          : `${validation.pending} en attente de traitement`,
      trend: null,
    },
    {
      description: "Inscriptions ce mois-ci",
      main: <CountUp value={registrations.current} />,
      footer: `${registrations.previous} le mois précédent`,
      trend: registrations.variation,
    },
  ]

  return (
    <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-3">
      {cards.map((card, index) => (
        <Card
          key={card.description}
          className="@container/card animate-in transition-shadow duration-500 fade-in-0 fill-mode-both slide-in-from-bottom-4 hover:shadow-md"
          style={{ animationDelay: `${index * 90}ms` }}
        >
          <CardHeader>
            <CardDescription className="flex items-center justify-between">
              {card.description}
              {card.trend !== undefined ? (
                <TrendBadge variation={card.trend} />
              ) : null}
            </CardDescription>
            <CardTitle className="text-3xl font-semibold tabular-nums">
              {card.main}
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
