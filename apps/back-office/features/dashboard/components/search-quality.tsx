"use client"

import type { StatsSearchesDto } from "@workspace/modules/stats"

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  StatCardGrid,
  type StatCardData,
} from "@/features/dashboard/components/stat-card"

const FILTER_LABELS: Record<string, string> = {
  sort: "Tri",
  min_rating: "Note minimale",
  max_price: "Prix maximum",
  radius: "Rayon",
  host_type: "Type d'hôte",
  date_from: "Date de début",
  date_to: "Date de fin",
}

export function SearchQuality({ searches }: { searches: StatsSearchesDto }) {
  const cards: StatCardData[] = [
    {
      description: "Recherches sans résultat",
      value: searches.zero_result_rate,
      footer: `${searches.zero_result_count} sur ${searches.total} recherches`,
    },
  ]

  const maxFilter = Math.max(...searches.top_filters.map((f) => f.total), 1)

  return (
    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div className="flex flex-col justify-center">
        <StatCardGrid cards={cards} columns={2} />
      </div>
      <Card className="mx-4 lg:mx-6">
        <CardHeader>
          <CardDescription>Comportement de recherche</CardDescription>
          <CardTitle>Filtres les plus utilisés</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-col gap-2">
          {searches.top_filters.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted-foreground">
              Aucun filtre utilisé pour l&apos;instant.
            </p>
          ) : (
            searches.top_filters.map((filter) => (
              <div
                key={filter.filter}
                className="flex items-center gap-3 text-sm"
              >
                <span className="w-28 shrink-0 truncate">
                  {FILTER_LABELS[filter.filter] ?? filter.filter}
                </span>
                <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted">
                  <div
                    className="h-full rounded-full bg-primary transition-all duration-700"
                    style={{ width: `${(filter.total / maxFilter) * 100}%` }}
                  />
                </div>
                <span className="w-8 shrink-0 text-end text-muted-foreground tabular-nums">
                  {filter.total}
                </span>
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  )
}
