"use client"

import { Area, AreaChart, CartesianGrid, XAxis } from "recharts"

import type { StatsFinanceDto } from "@workspace/modules/stats"

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  type ChartConfig,
} from "@/components/ui/chart"
import { CountUp } from "@/features/dashboard/components/count-up"
import { TrendBadge } from "@/features/dashboard/components/trend-badge"

const gmvConfig = {
  total: { label: "GMV (€)", color: "var(--chart-1)" },
} satisfies ChartConfig

function formatMonth(month: string): string {
  const [year, monthNumber] = month.split("-")
  return `${monthNumber}/${year?.slice(2) ?? ""}`
}

export function FinanceSection({ finance }: { finance: StatsFinanceDto }) {
  const cards = [
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
      description: "Panier moyen",
      main: <CountUp value={finance.avg_basket} decimals={0} suffix=" €" />,
      footer: `Reversé aux pros : ${finance.net_to_pros.toLocaleString("fr-FR")} €`,
      trend: undefined,
    },
    {
      description: "Remboursements",
      main: <CountUp value={finance.refunds} decimals={0} suffix=" €" />,
      footer: `Taux : ${finance.refund_rate} %`,
      trend: undefined,
    },
  ]

  const chartData = finance.gmv_growth.series.map((entry) => ({
    label: formatMonth(entry.month),
    total: entry.total,
  }))

  return (
    <div className="flex flex-col gap-4">
      <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
        {cards.map((card, index) => (
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
          </Card>
        ))}
      </div>

      <Card className="mx-4 lg:mx-6">
        <CardHeader>
          <CardDescription>
            Évolution du chiffre d&apos;affaires
          </CardDescription>
          <CardTitle>GMV mensuelle (6 derniers mois)</CardTitle>
        </CardHeader>
        <CardContent>
          {chartData.every((d) => d.total === 0) ? (
            <p className="py-8 text-center text-sm text-muted-foreground">
              Aucune réservation payée sur la période.
            </p>
          ) : (
            <ChartContainer config={gmvConfig} className="h-64 w-full">
              <AreaChart accessibilityLayer data={chartData}>
                <CartesianGrid vertical={false} />
                <XAxis
                  dataKey="label"
                  tickLine={false}
                  axisLine={false}
                  tickMargin={8}
                />
                <ChartTooltip content={<ChartTooltipContent />} />
                <Area
                  dataKey="total"
                  type="monotone"
                  fill="var(--color-total)"
                  fillOpacity={0.2}
                  stroke="var(--color-total)"
                  strokeWidth={2}
                />
              </AreaChart>
            </ChartContainer>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
