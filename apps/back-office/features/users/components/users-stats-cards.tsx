"use client"

import { useMemo } from "react"

import { AdminUserModel } from "@workspace/modules/admin"

import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

export function UsersStatsCards({ users }: { users: AdminUserModel[] }) {
  const stats = useMemo(
    () => ({
      total: users.length,
      active: users.filter((user) => user.isActive()).length,
      verified: users.filter((user) => user.isIdVerified).length,
      banned: users.filter((user) => user.isBanned).length,
    }),
    [users]
  )

  const cards = [
    {
      description: "Utilisateurs chargés",
      value: stats.total,
      footer: "Résultats correspondant aux filtres (max. 100)",
    },
    {
      description: "Comptes actifs",
      value: stats.active,
      footer: "Statut actif, non bannis",
    },
    {
      description: "Identités vérifiées",
      value: stats.verified,
      footer: "Pièce d'identité validée",
    },
    {
      description: "Comptes bannis",
      value: stats.banned,
      footer: "Accès actuellement suspendu",
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
