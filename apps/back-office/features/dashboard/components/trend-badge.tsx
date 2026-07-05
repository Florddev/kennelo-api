"use client"

import { HugeiconsIcon } from "@hugeicons/react"
import {
  ArrowDown01Icon,
  ArrowUp01Icon,
  MinusSignIcon,
} from "@hugeicons/core-free-icons"

import { cn } from "@/lib/utils"

export function TrendBadge({ variation }: { variation: number | null }) {
  if (variation === null) {
    return (
      <span className="inline-flex items-center gap-0.5 rounded-full bg-muted px-1.5 py-0.5 text-xs font-medium text-muted-foreground">
        <HugeiconsIcon
          icon={MinusSignIcon}
          strokeWidth={2}
          className="size-3"
        />
        n/a
      </span>
    )
  }

  const positive = variation >= 0

  return (
    <span
      className={cn(
        "inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium",
        positive
          ? "bg-green-500/10 text-green-600 dark:text-green-400"
          : "bg-destructive/10 text-destructive"
      )}
    >
      <HugeiconsIcon
        icon={positive ? ArrowUp01Icon : ArrowDown01Icon}
        strokeWidth={2}
        className="size-3"
      />
      {Math.abs(variation)}%
    </span>
  )
}
