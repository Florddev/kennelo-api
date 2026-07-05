"use client"

import type { ProspectionFunnelDto } from "@workspace/modules/stats"

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { cn } from "@/lib/utils"

type Step = {
  label: string
  value: number
  colorClass: string
  rate?: { label: string; value: number }
}

export function ProspectionFunnel({
  funnel,
}: {
  funnel: ProspectionFunnelDto
}) {
  const max = Math.max(funnel.identified, 1)

  const steps: Step[] = [
    {
      label: "Identifiés",
      value: funnel.identified,
      colorClass: "bg-blue-500",
    },
    {
      label: "Contactés",
      value: funnel.contacted,
      colorClass: "bg-amber-500",
      rate: { label: "prise de contact", value: funnel.contact_rate },
    },
    {
      label: "Inscrits",
      value: funnel.registered,
      colorClass: "bg-green-500",
      rate: { label: "conversion", value: funnel.conversion_rate },
    },
  ]

  return (
    <Card className="mx-4 lg:mx-6">
      <CardHeader>
        <CardDescription>Entonnoir de prospection</CardDescription>
        <CardTitle>Du prospect identifié à l&apos;inscription</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        {steps.map((step, index) => (
          <div
            key={step.label}
            className="flex animate-in flex-col gap-1 fade-in-0 fill-mode-both slide-in-from-left-4"
            style={{ animationDelay: `${index * 120}ms` }}
          >
            <div className="flex items-center justify-between text-sm">
              <span className="font-medium">{step.label}</span>
              <span className="flex items-center gap-2">
                <span className="tabular-nums">
                  {step.value.toLocaleString("fr-FR")}
                </span>
                {step.rate ? (
                  <span className="text-xs text-muted-foreground">
                    {step.rate.value}% {step.rate.label}
                  </span>
                ) : null}
              </span>
            </div>
            <div className="h-3 overflow-hidden rounded-full bg-muted">
              <div
                className={cn(
                  "h-full rounded-full transition-all duration-700 ease-out",
                  step.colorClass
                )}
                style={{ width: `${Math.max((step.value / max) * 100, 2)}%` }}
              />
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
