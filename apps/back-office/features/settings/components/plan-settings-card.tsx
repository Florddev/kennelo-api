"use client"

import { useState } from "react"

import { ArrowDown01Icon } from "@hugeicons/core-free-icons"
import { HugeiconsIcon } from "@hugeicons/react"

import {
  SubscriptionPlanModel,
  updateSubscriptionPlan,
  updateSubscriptionPlanSchema,
} from "@workspace/modules/settings"

import { Card } from "@/components/ui/card"
import {
  Collapsible,
  CollapsiblePanel,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import { Badge } from "@/components/ui/badge"
import { Field, FieldError, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Button } from "@/components/ui/button"
import { SettingNumberField } from "@/features/settings/components/setting-number-field"
import { useSettingsAction } from "@/features/settings/hooks/use-settings-action"
import {
  formatRateAsPercent,
  percentInputToRate,
  rateToPercentInput,
} from "@/features/settings/lib/format"
import { cn } from "@/lib/utils"

type FieldErrors = Partial<Record<string, string>>

export function PlanSettingsCard({
  plan,
  onSaved,
}: {
  plan: SubscriptionPlanModel
  onSaved: () => void | Promise<void>
}) {
  const { busy, run } = useSettingsAction()

  const [open, setOpen] = useState(false)
  const [name, setName] = useState(plan.name)
  const [priceMonthly, setPriceMonthly] = useState(plan.priceMonthly)
  const [commissionPercent, setCommissionPercent] = useState(
    rateToPercentInput(plan.commissionRate)
  )
  const [maxActivities, setMaxActivities] = useState(
    String(plan.limits.maxActivities ?? -1)
  )
  const [maxCycles, setMaxCycles] = useState(
    String(plan.limits.maxCyclesPerActivity ?? -1)
  )
  const [maxPhotos, setMaxPhotos] = useState(
    String(plan.limits.maxPhotos ?? -1)
  )
  const [isActive, setIsActive] = useState(plan.isActive)
  const [errors, setErrors] = useState<FieldErrors>({})

  const onSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setErrors({})

    const parsed = updateSubscriptionPlanSchema.safeParse({
      name,
      description: plan.description,
      priceMonthly,
      commissionRate: percentInputToRate(commissionPercent),
      maxActivities,
      maxCyclesPerActivity: maxCycles,
      maxPhotos,
      isActive,
    })

    if (!parsed.success) {
      const flattened = parsed.error.flatten().fieldErrors
      setErrors(
        Object.fromEntries(
          Object.entries(flattened).map(([key, value]) => [key, value?.[0]])
        )
      )
      return
    }

    await run(
      () => updateSubscriptionPlan(plan.id, parsed.data),
      `Plan « ${plan.name} » enregistré.`,
      onSaved
    )
  }

  return (
    <Card className="overflow-hidden py-0">
      <Collapsible open={open} onOpenChange={setOpen}>
        <CollapsibleTrigger
          render={<button type="button" />}
          className="flex w-full items-center justify-between gap-4 px-6 py-4 text-start transition-colors hover:bg-muted/50"
        >
          <div className="flex flex-col gap-1">
            <span className="flex items-center gap-2 font-medium">
              {plan.name}
              <Badge variant={plan.isActive ? "default" : "outline"}>
                {plan.isActive ? "Actif" : "Inactif"}
              </Badge>
            </span>
            <span className="text-sm text-muted-foreground">
              {plan.priceMonthly} {plan.currency} / mois — commission{" "}
              {formatRateAsPercent(plan.commissionRate)}
            </span>
          </div>
          <HugeiconsIcon
            icon={ArrowDown01Icon}
            strokeWidth={2}
            className={cn(
              "size-5 shrink-0 text-muted-foreground transition-transform",
              open && "rotate-180"
            )}
          />
        </CollapsibleTrigger>

        <CollapsiblePanel>
          <div className="border-t px-6 py-6">
            <p className="mb-6 text-sm text-muted-foreground">
              {plan.slug} — les identifiants Stripe restent gérés côté Stripe.
            </p>
            <form
              onSubmit={onSubmit}
              className="flex flex-col gap-6"
              noValidate
            >
              <div className="grid gap-6 sm:grid-cols-2">
                <Field data-invalid={errors.name ? true : undefined}>
                  <FieldLabel htmlFor={`name-${plan.id}`}>Nom</FieldLabel>
                  <Input
                    id={`name-${plan.id}`}
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    aria-invalid={errors.name ? true : undefined}
                    disabled={busy}
                  />
                  <FieldError>{errors.name}</FieldError>
                </Field>

                <SettingNumberField
                  id={`price-${plan.id}`}
                  label={`Prix mensuel (${plan.currency})`}
                  value={priceMonthly}
                  onChange={setPriceMonthly}
                  error={errors.priceMonthly}
                  disabled={busy}
                  step="0.01"
                  min="0"
                />

                <SettingNumberField
                  id={`commission-${plan.id}`}
                  label="Commission (%)"
                  value={commissionPercent}
                  onChange={setCommissionPercent}
                  error={errors.commissionRate}
                  disabled={busy}
                  step="0.1"
                  min="0"
                  max="100"
                />
              </div>

              <div className="grid gap-6 sm:grid-cols-3">
                <SettingNumberField
                  id={`activities-${plan.id}`}
                  label="Nombre d'activités"
                  hint="Mettez -1 pour ne pas mettre de limite."
                  value={maxActivities}
                  onChange={setMaxActivities}
                  error={errors.maxActivities}
                  disabled={busy}
                  min="-1"
                />

                <SettingNumberField
                  id={`cycles-${plan.id}`}
                  label="Cycles par activité"
                  hint="Mettez -1 pour ne pas mettre de limite."
                  value={maxCycles}
                  onChange={setMaxCycles}
                  error={errors.maxCyclesPerActivity}
                  disabled={busy}
                  min="-1"
                />

                <SettingNumberField
                  id={`photos-${plan.id}`}
                  label="Nombre de photos"
                  hint="Mettez -1 pour ne pas mettre de limite."
                  value={maxPhotos}
                  onChange={setMaxPhotos}
                  error={errors.maxPhotos}
                  disabled={busy}
                  min="-1"
                />
              </div>

              <div className="flex items-center justify-between">
                <Label className="gap-3">
                  <Switch
                    id={`active-${plan.id}`}
                    checked={isActive}
                    onCheckedChange={setIsActive}
                    disabled={busy}
                  />
                  Plan actif
                </Label>

                <Button render={<button type="submit" />} loading={busy}>
                  Enregistrer
                </Button>
              </div>
            </form>
          </div>
        </CollapsiblePanel>
      </Collapsible>
    </Card>
  )
}
