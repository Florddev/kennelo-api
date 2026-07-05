"use client"

import { Bar, BarChart, CartesianGrid, XAxis } from "recharts"

import type { StatsSearchesDto } from "@workspace/modules/stats"

import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
  CardContent,
} from "@/components/ui/card"
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  type ChartConfig,
} from "@/components/ui/chart"

const monthlyConfig = {
  total: { label: "Recherches", color: "var(--chart-1)" },
} satisfies ChartConfig

const departmentConfig = {
  total: { label: "Recherches", color: "var(--chart-2)" },
} satisfies ChartConfig

function formatMonth(month: string): string {
  const [year, monthNumber] = month.split("-")
  return `${monthNumber}/${year?.slice(2) ?? ""}`
}

export function SearchCharts({ searches }: { searches: StatsSearchesDto }) {
  const monthlyData = searches.monthly.map((entry) => ({
    label: formatMonth(entry.month),
    total: entry.total,
  }))

  const departmentData = searches.by_department.slice(0, 12).map((entry) => ({
    label: entry.department,
    total: entry.total,
  }))

  return (
    <div className="grid grid-cols-1 gap-4 px-4 lg:grid-cols-2 lg:px-6">
      <Card>
        <CardHeader>
          <CardDescription>Évolution mensuelle</CardDescription>
          <CardTitle>Recherches par mois (12 derniers mois)</CardTitle>
        </CardHeader>
        <CardContent>
          {monthlyData.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted-foreground">
              Aucune recherche enregistrée pour l&apos;instant.
            </p>
          ) : (
            <ChartContainer config={monthlyConfig} className="h-64 w-full">
              <BarChart accessibilityLayer data={monthlyData}>
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
          <CardDescription>Répartition géographique</CardDescription>
          <CardTitle>Top départements</CardTitle>
        </CardHeader>
        <CardContent>
          {departmentData.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted-foreground">
              Aucune donnée géographique disponible.
            </p>
          ) : (
            <ChartContainer config={departmentConfig} className="h-64 w-full">
              <BarChart accessibilityLayer data={departmentData}>
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
    </div>
  )
}
