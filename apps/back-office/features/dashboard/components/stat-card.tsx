"use client"

import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

export type StatCardData = {
  description: string
  value: number
  footer: string
}

export function StatCardGrid({
  cards,
  columns = 3,
}: {
  cards: StatCardData[]
  columns?: 2 | 3 | 4
}) {
  const columnClass =
    columns === 4
      ? "@xl/main:grid-cols-2 @5xl/main:grid-cols-4"
      : columns === 2
        ? "@xl/main:grid-cols-2"
        : "@xl/main:grid-cols-2 @5xl/main:grid-cols-3"

  return (
    <div
      className={`grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-linear-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 dark:*:data-[slot=card]:bg-card ${columnClass}`}
    >
      {cards.map((card, index) => (
        <Card
          key={card.description}
          className="@container/card animate-in transition-shadow duration-500 fade-in-0 fill-mode-both slide-in-from-bottom-4 hover:shadow-md"
          style={{ animationDelay: `${index * 80}ms` }}
        >
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
