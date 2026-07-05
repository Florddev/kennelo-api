"use client"

import { Cell, Pie, PieChart } from "recharts"

import type { StatsCommunityDto } from "@workspace/modules/stats"

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

const rolesConfig = {
  owners: { label: "Propriétaires", color: "var(--chart-1)" },
  pros: { label: "Professionnels", color: "var(--chart-2)" },
  admins: { label: "Admins", color: "var(--chart-4)" },
} satisfies ChartConfig

export function CommunitySection({
  community,
}: {
  community: StatsCommunityDto
}) {
  const cards: StatCardData[] = [
    {
      description: "Croissance ce mois",
      value: community.user_growth.current,
      footer: `${community.user_growth.previous} le mois précédent`,
    },
    {
      description: "Actifs (30 j)",
      value: community.active_users.mau,
      footer: `${community.active_users.wau} / 7j · ${community.active_users.dau} / 24h`,
    },
    {
      description: "Taux de vérification KYC",
      value: community.kyc_rate,
      footer: `Email vérifié : ${community.email_verified_rate} %`,
    },
    {
      description: "Note moyenne des pros",
      value: community.avg_pro_rating ?? 0,
      footer: `Réponse aux avis : ${community.review_response_rate} %`,
    },
  ]

  const rolesData = [
    {
      key: "owners",
      label: "Propriétaires",
      value: community.roles_split.owners,
      fill: "var(--color-owners)",
    },
    {
      key: "pros",
      label: "Professionnels",
      value: community.roles_split.pros,
      fill: "var(--color-pros)",
    },
    {
      key: "admins",
      label: "Admins",
      value: community.roles_split.admins,
      fill: "var(--color-admins)",
    },
  ].filter((row) => row.value > 0)

  const ratingRows = Object.entries(community.rating_distribution)
    .map(([star, total]) => ({ star: Number(star), total }))
    .sort((a, b) => b.star - a.star)
  const maxRating = Math.max(...ratingRows.map((r) => r.total), 1)

  return (
    <div className="flex flex-col gap-4">
      <StatCardGrid cards={cards} columns={4} />
      <div className="grid grid-cols-1 gap-4 px-4 lg:grid-cols-2 lg:px-6">
        <Card>
          <CardHeader>
            <CardDescription>Communauté</CardDescription>
            <CardTitle>Répartition des utilisateurs</CardTitle>
          </CardHeader>
          <CardContent>
            {rolesData.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                Aucun utilisateur.
              </p>
            ) : (
              <ChartContainer
                config={rolesConfig}
                className="mx-auto h-64 w-full"
              >
                <PieChart>
                  <ChartTooltip
                    content={<ChartTooltipContent nameKey="label" />}
                  />
                  <Pie
                    data={rolesData}
                    dataKey="value"
                    nameKey="label"
                    innerRadius={55}
                    strokeWidth={2}
                  >
                    {rolesData.map((entry) => (
                      <Cell key={entry.key} fill={entry.fill} />
                    ))}
                  </Pie>
                </PieChart>
              </ChartContainer>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Satisfaction</CardDescription>
            <CardTitle>Distribution des notes</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col gap-2">
            {ratingRows.every((r) => r.total === 0) ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                Aucun avis publié.
              </p>
            ) : (
              ratingRows.map((row) => (
                <div key={row.star} className="flex items-center gap-3 text-sm">
                  <span className="w-8 shrink-0 tabular-nums">{row.star}★</span>
                  <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-full rounded-full bg-amber-500 transition-all duration-700"
                      style={{ width: `${(row.total / maxRating) * 100}%` }}
                    />
                  </div>
                  <span className="w-8 shrink-0 text-end text-muted-foreground tabular-nums">
                    {row.total}
                  </span>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
      <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2">
        <Card className="@container/card">
          <CardHeader>
            <CardDescription>Messages (30 derniers jours)</CardDescription>
            <CardTitle className="text-2xl font-semibold tabular-nums">
              {community.messages_last_30_days.toLocaleString("fr-FR")}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card className="@container/card">
          <CardHeader>
            <CardDescription>Conversations actives</CardDescription>
            <CardTitle className="text-2xl font-semibold tabular-nums">
              {community.active_conversations.toLocaleString("fr-FR")}
            </CardTitle>
          </CardHeader>
        </Card>
      </div>
    </div>
  )
}
