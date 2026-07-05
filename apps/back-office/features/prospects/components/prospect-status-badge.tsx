"use client"

import {
  PROSPECT_STATUS,
  type ProspectStatusValue,
} from "@workspace/modules/prospects"

import { Badge } from "@/components/ui/badge"

type BadgeVariant =
  | "default"
  | "secondary"
  | "success"
  | "info"
  | "warning"
  | "destructive"
  | "outline"

const STATUS_BADGE: Record<
  ProspectStatusValue,
  { label: string; variant: BadgeVariant }
> = {
  [PROSPECT_STATUS.NON_CONTACTE]: { label: "Non contacté", variant: "outline" },
  [PROSPECT_STATUS.CONTACTE]: { label: "Contacté", variant: "info" },
  [PROSPECT_STATUS.RELANCE]: { label: "Relancé", variant: "warning" },
  [PROSPECT_STATUS.INSCRIT]: { label: "Inscrit", variant: "success" },
  [PROSPECT_STATUS.REFUSE]: { label: "Refusé", variant: "destructive" },
}

export function ProspectStatusBadge({
  status,
}: {
  status: ProspectStatusValue
}) {
  const badge = STATUS_BADGE[status]
  return <Badge variant={badge.variant}>{badge.label}</Badge>
}

export const PROSPECT_STATUS_OPTIONS = [
  { label: "Tous les statuts", value: "" },
  { label: "Non contacté", value: PROSPECT_STATUS.NON_CONTACTE },
  { label: "Contacté", value: PROSPECT_STATUS.CONTACTE },
  { label: "Relancé", value: PROSPECT_STATUS.RELANCE },
  { label: "Inscrit", value: PROSPECT_STATUS.INSCRIT },
  { label: "Refusé", value: PROSPECT_STATUS.REFUSE },
]
