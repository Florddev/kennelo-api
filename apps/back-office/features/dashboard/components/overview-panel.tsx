"use client"

import type {
  StatsBookingsDto,
  StatsBusinessDto,
  StatsCommunityDto,
  StatsFinanceDto,
} from "@workspace/modules/stats"

import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { BusinessKpiCards } from "@/features/dashboard/components/business-kpi-cards"
import { CountUp } from "@/features/dashboard/components/count-up"
import { MarketOpportunities } from "@/features/dashboard/components/market-opportunities"
import { ProspectionFunnel } from "@/features/dashboard/components/prospection-funnel"
import { TrendBadge } from "@/features/dashboard/components/trend-badge"

export function OverviewPanel({
  business,
  finance,
  bookings,
  community,
}: {
  business: StatsBusinessDto
  finance: StatsFinanceDto
  bookings: StatsBookingsDto
  community: StatsCommunityDto
}) {
  const heroCards = [
    {
      description: "Volume d'affaires (GMV)",
      main: <CountUp value={finance.gmv} decimals={0} suffix=" €" />,
      footer: `${finance.paid_bookings} réservations payées`,
      trend: finance.gmv_growth.variation,
    },
    {
      description: "Revenu Kennelo",
      main: (
        <CountUp value={finance.kennelo_revenue} decimals={0} suffix=" €" />
      ),
      footer: `Take rate : ${finance.take_rate} %`,
      trend: undefined,
    },
    {
      description: "Réservations",
      main: <CountUp value={bookings.total} />,
      footer: `Conversion paiement : ${bookings.payment_conversion_rate} %`,
      trend: undefined,
    },
    {
      description: "Utilisateurs actifs (MAU)",
      main: <CountUp value={community.active_users.mau} />,
      footer: `${community.active_users.dau} aujourd'hui · ${community.active_users.wau} cette semaine`,
      trend: community.user_growth.variation,
    },
  ]

  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
        {heroCards.map((card, index) => (
          <Card
            key={card.description}
            className="@container/card animate-in transition-shadow duration-500 fade-in-0 fill-mode-both slide-in-from-bottom-4 hover:shadow-md"
            style={{ animationDelay: `${index * 80}ms` }}
          >
            <CardHeader>
              <CardDescription className="flex items-center justify-between">
                {card.description}
                {card.trend !== undefined ? (
                  <TrendBadge variation={card.trend} />
                ) : null}
              </CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {card.main}
              </CardTitle>
            </CardHeader>
            <CardFooter className="text-sm text-muted-foreground">
              {card.footer}
            </CardFooter>
          </Card>
        ))}
      </div>

      <BusinessKpiCards business={business} />

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <ProspectionFunnel funnel={business.prospection_funnel} />
        <MarketOpportunities coverage={business.market_coverage} />
      </div>
    </div>
  )
}
