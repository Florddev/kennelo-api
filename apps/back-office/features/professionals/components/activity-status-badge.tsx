"use client"

import {
  ACTIVITY_STATUS,
  type ActivityStatusValue,
} from "@workspace/modules/professionals"

import { Badge } from "@/components/ui/badge"

type BadgeVariant = "success" | "warning" | "destructive" | "outline"

const STATUS_BADGE: Record<
  ActivityStatusValue,
  { label: string; variant: BadgeVariant }
> = {
  [ACTIVITY_STATUS.PENDING]: { label: "En attente", variant: "warning" },
  [ACTIVITY_STATUS.APPROVED]: { label: "Validé", variant: "success" },
  [ACTIVITY_STATUS.REJECTED]: { label: "Refusé", variant: "destructive" },
}

export function ActivityStatusBadge({
  status,
}: {
  status: ActivityStatusValue
}) {
  const badge = STATUS_BADGE[status]
  return <Badge variant={badge.variant}>{badge.label}</Badge>
}

export const ACTIVITY_STATUS_OPTIONS = [
  { label: "Tous les statuts", value: "all" },
  { label: "En attente", value: ACTIVITY_STATUS.PENDING },
  { label: "Validé", value: ACTIVITY_STATUS.APPROVED },
  { label: "Refusé", value: ACTIVITY_STATUS.REJECTED },
]
