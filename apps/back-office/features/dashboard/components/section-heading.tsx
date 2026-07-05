"use client"

import type { ReactNode } from "react"
import { HugeiconsIcon } from "@hugeicons/react"

import { cn } from "@/lib/utils"

type SectionHeadingProps = {
  icon: Parameters<typeof HugeiconsIcon>[0]["icon"]
  title: string
  description?: string
  className?: string
  children?: ReactNode
}

export function SectionHeading({
  icon,
  title,
  description,
  className,
  children,
}: SectionHeadingProps) {
  return (
    <div
      className={cn(
        "flex animate-in items-start justify-between gap-4 px-4 fade-in-0 fill-mode-both slide-in-from-left-2 lg:px-6",
        className
      )}
    >
      <div className="flex items-center gap-3">
        <div className="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <HugeiconsIcon icon={icon} strokeWidth={2} className="size-5" />
        </div>
        <div className="grid gap-0.5">
          <h2 className="text-base leading-tight font-semibold">{title}</h2>
          {description ? (
            <p className="text-sm text-muted-foreground">{description}</p>
          ) : null}
        </div>
      </div>
      {children ? <div className="shrink-0">{children}</div> : null}
    </div>
  )
}
