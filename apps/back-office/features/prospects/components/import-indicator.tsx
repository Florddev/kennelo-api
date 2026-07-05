"use client"

import { HugeiconsIcon } from "@hugeicons/react"
import { Loading03Icon } from "@hugeicons/core-free-icons"

import { Badge } from "@/components/ui/badge"
import { useImportProgress } from "@/features/prospects/hooks/use-import-progress"

export function ImportIndicator() {
  const { pendingCount } = useImportProgress()

  if (pendingCount === 0) {
    return null
  }

  return (
    <Badge variant="info" className="gap-1.5">
      <HugeiconsIcon
        icon={Loading03Icon}
        strokeWidth={2}
        className="size-3.5 animate-spin"
      />
      {pendingCount > 1
        ? `${pendingCount} imports en cours…`
        : "Import en cours…"}
    </Badge>
  )
}
