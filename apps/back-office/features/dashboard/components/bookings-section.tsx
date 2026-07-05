"use client"

import { Bar, BarChart, CartesianGrid, XAxis } from "recharts"

import type { StatsBookingsDto } from "@workspace/modules/stats"

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
import {
  StatCardGrid,
  type StatCardData,
} from "@/features/dashboard/components/stat-card"

const monthlyConfig = {
  total: { label: "Réservations", color: "var(--chart-3)" },
} satisfies ChartConfig

const STATUS_LABELS: Record<string, string> = {
  pending: "En attente",
  confirmed: "Confirmées",
  in_progress: "En cours",
  completed: "Terminées",
  cancelled: "Annulées",
  rejected: "Refusées",
  expired: "Expirées",
}

function formatMonth(month: string): string {
  const [year, monthNumber] = month.split("-")
  return `${monthNumber}/${year?.slice(2) ?? ""}`
}

export function BookingsSection({ bookings }: { bookings: StatsBookingsDto }) {
  const cards: StatCardData[] = [
    {
      description: "Réservations totales",
      value: bookings.total,
      footer: "Tous statuts confondus",
    },
    {
      description: "Taux d'annulation",
      value: bookings.cancellation_rate,
      footer: "Annulées / refusées / expirées",
    },
    {
      description: "Taux de conversion paiement",
      value: bookings.payment_conversion_rate,
      footer: "Payées / créées",
    },
    {
      description: "Durée moyenne de séjour",
      value: bookings.avg_stay_nights ?? 0,
      footer: "Nuits par réservation",
    },
  ]

  const chartData = bookings.monthly.series.map((entry) => ({
    label: formatMonth(entry.month),
    total: entry.total,
  }))

  const statusData = Object.entries(bookings.by_status)
    .filter(([, total]) => total > 0)
    .map(([status, total]) => ({
      label: STATUS_LABELS[status] ?? status,
      total,
    }))

  return (
    <div className="flex flex-col gap-4">
      <StatCardGrid cards={cards} columns={4} />
      <div className="grid grid-cols-1 gap-4 px-4 lg:grid-cols-2 lg:px-6">
        <Card>
          <CardHeader>
            <CardDescription>Volume</CardDescription>
            <CardTitle>Réservations par mois</CardTitle>
          </CardHeader>
          <CardContent>
            {chartData.every((d) => d.total === 0) ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                Aucune réservation.
              </p>
            ) : (
              <ChartContainer config={monthlyConfig} className="h-64 w-full">
                <BarChart accessibilityLayer data={chartData}>
                  <CartesianGrid vertical={false} />
                  <XAxis
                    dataKey="label"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                  />
                  <ChartTooltip content={<ChartTooltipContent />} />
                  <Bar dataKey="total" fill="var(--color-total)" radius={4} />
                </BarChart>
              </ChartContainer>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Répartition</CardDescription>
            <CardTitle>Réservations par statut</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col gap-2">
            {statusData.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                Aucune réservation.
              </p>
            ) : (
              statusData.map((row) => (
                <div
                  key={row.label}
                  className="flex items-center justify-between text-sm"
                >
                  <span>{row.label}</span>
                  <span className="font-medium tabular-nums">{row.total}</span>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
