"use client"

import { useState } from "react"

import {
  SettingsModel,
  updateSettings,
  updateSettingsSchema,
} from "@workspace/modules/settings"

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Field, FieldError, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { SettingNumberField } from "@/features/settings/components/setting-number-field"
import { SettingSwitchField } from "@/features/settings/components/setting-switch-field"
import { useSettingsAction } from "@/features/settings/hooks/use-settings-action"
import {
  percentInputToRate,
  rateToPercentInput,
} from "@/features/settings/lib/format"

type FieldErrors = Partial<Record<string, string>>

export function GeneralSettingsForm({
  settings,
  onSaved,
}: {
  settings: SettingsModel
  onSaved: () => void | Promise<void>
}) {
  const { busy, run } = useSettingsAction()

  const [userServiceFeePercent, setUserServiceFeePercent] = useState(
    rateToPercentInput(settings.userServiceFeeRate)
  )
  const [hostCommissionPercent, setHostCommissionPercent] = useState(
    rateToPercentInput(settings.hostCommissionRate)
  )
  const [acceptanceWindowHours, setAcceptanceWindowHours] = useState(
    String(settings.acceptanceWindowHours)
  )
  const [payoutDelayHours, setPayoutDelayHours] = useState(
    String(settings.payoutDelayHours)
  )
  const [reminderAfterHours, setReminderAfterHours] = useState(
    String(settings.reminderAfterHours)
  )
  const [currency, setCurrency] = useState(settings.currency)
  const [tier3Enabled, setTier3Enabled] = useState(settings.tier3Enabled)
  const [softDisableActivities, setSoftDisableActivities] = useState(
    settings.softDisableActivities
  )
  const [softDisableCycles, setSoftDisableCycles] = useState(
    settings.softDisableCycles
  )
  const [softDisablePhotos, setSoftDisablePhotos] = useState(
    settings.softDisablePhotos
  )
  const [errors, setErrors] = useState<FieldErrors>({})

  const onSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setErrors({})

    const parsed = updateSettingsSchema.safeParse({
      userServiceFeeRate: percentInputToRate(userServiceFeePercent),
      hostCommissionRate: percentInputToRate(hostCommissionPercent),
      acceptanceWindowHours,
      payoutDelayHours,
      reminderAfterHours,
      currency,
      tier3Enabled,
      softDisableActivities,
      softDisableCycles,
      softDisablePhotos,
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
      () => updateSettings(parsed.data),
      "Paramètres enregistrés.",
      onSaved
    )
  }

  return (
    <form onSubmit={onSubmit} className="flex flex-col gap-6" noValidate>
      <Card>
        <CardHeader>
          <CardTitle>Frais et commissions</CardTitle>
          <CardDescription>Taux exprimés en pourcentage.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-6 sm:grid-cols-2">
          <SettingNumberField
            id="userServiceFeeRate"
            label="Frais de service utilisateur (%)"
            value={userServiceFeePercent}
            onChange={setUserServiceFeePercent}
            error={errors.userServiceFeeRate}
            disabled={busy}
            step="0.1"
            min="0"
            max="100"
          />

          <SettingNumberField
            id="hostCommissionRate"
            label="Commission hôte (%)"
            value={hostCommissionPercent}
            onChange={setHostCommissionPercent}
            error={errors.hostCommissionRate}
            disabled={busy}
            step="0.1"
            min="0"
            max="100"
          />

          <Field data-invalid={errors.currency ? true : undefined}>
            <FieldLabel htmlFor="currency">Devise</FieldLabel>
            <Input
              id="currency"
              value={currency}
              onChange={(event) => setCurrency(event.target.value)}
              aria-invalid={errors.currency ? true : undefined}
              disabled={busy}
            />
            <FieldError>{errors.currency}</FieldError>
          </Field>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Délais de réservation</CardTitle>
          <CardDescription>Valeurs exprimées en heures.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-6 sm:grid-cols-3">
          <SettingNumberField
            id="acceptanceWindowHours"
            label="Fenêtre d'acceptation"
            value={acceptanceWindowHours}
            onChange={setAcceptanceWindowHours}
            error={errors.acceptanceWindowHours}
            disabled={busy}
            min="0"
          />

          <SettingNumberField
            id="payoutDelayHours"
            label="Délai de versement"
            value={payoutDelayHours}
            onChange={setPayoutDelayHours}
            error={errors.payoutDelayHours}
            disabled={busy}
            min="0"
          />

          <SettingNumberField
            id="reminderAfterHours"
            label="Rappel après"
            value={reminderAfterHours}
            onChange={setReminderAfterHours}
            error={errors.reminderAfterHours}
            disabled={busy}
            min="0"
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Notifications d&apos;activité</CardTitle>
          <CardDescription>
            Ce sont les petites alertes du quotidien. Un favori ajouté, une
            fiche modifiée, un animal créé ou supprimé. Utiles, mais pas
            urgentes.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <SettingSwitchField
            id="tier3Enabled"
            label="Envoyer les notifications d'activité"
            description="Coupez-les pour laisser vos utilisateurs tranquilles et ne garder que l'essentiel comme les réservations, les paiements et les messages."
            checked={tier3Enabled}
            onCheckedChange={setTier3Enabled}
            disabled={busy}
            whenOn="vos utilisateurs reçoivent ces petites alertes."
            whenOff="vos utilisateurs ne reçoivent que les notifications importantes."
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Passage à un plan inférieur</CardTitle>
          <CardDescription>
            Quand un hôte descend vers un plan plus petit, il peut avoir plus de
            contenu que le nouveau plan n&apos;autorise. Vous choisissez ici si
            ce surplus est masqué tout seul. Rien n&apos;est jamais supprimé, et
            tout revient si l&apos;hôte reprend un plan supérieur.
          </CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col gap-4">
          <SettingSwitchField
            id="softDisableActivities"
            label="Masquer les activités en trop"
            description="Entre en jeu quand l'hôte a plus d'activités que son nouveau plan ne permet."
            checked={softDisableActivities}
            onCheckedChange={setSoftDisableActivities}
            disabled={busy}
            whenOn="les activités en trop sont masquées toutes seules."
            whenOff="toutes les activités restent visibles, même en trop grand nombre."
          />
          <SettingSwitchField
            id="softDisableCycles"
            label="Masquer les cycles en trop"
            description="Entre en jeu quand une activité a plus de cycles que son nouveau plan ne permet."
            checked={softDisableCycles}
            onCheckedChange={setSoftDisableCycles}
            disabled={busy}
            whenOn="les cycles en trop sont désactivés tout seuls."
            whenOff="tous les cycles restent actifs, même en trop grand nombre."
          />
          <SettingSwitchField
            id="softDisablePhotos"
            label="Masquer les photos en trop"
            description="Entre en jeu quand une fiche a plus de photos que son nouveau plan ne permet."
            checked={softDisablePhotos}
            onCheckedChange={setSoftDisablePhotos}
            disabled={busy}
            whenOn="les photos en trop sont masquées toutes seules."
            whenOff="toutes les photos restent visibles, même en trop grand nombre."
          />
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button render={<button type="submit" />} loading={busy}>
          Enregistrer
        </Button>
      </div>
    </form>
  )
}
