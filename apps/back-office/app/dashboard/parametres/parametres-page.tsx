"use client"

import { CreditCardIcon, MoneyBag02Icon } from "@hugeicons/core-free-icons"
import { HugeiconsIcon } from "@hugeicons/react"

import { SiteHeader } from "@/components/layout/site-header"
import { Spinner } from "@/components/ui/spinner"
import { Tabs, TabsList, TabsPanel, TabsTab } from "@/components/ui/tabs"
import { GeneralSettingsForm } from "@/features/settings/components/general-settings-form"
import { PlanSettingsCard } from "@/features/settings/components/plan-settings-card"
import { useSettings } from "@/features/settings/hooks/use-settings"

function SettingsBody({
  settings,
  plans,
  loading,
  error,
  refresh,
}: ReturnType<typeof useSettings>) {
  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="size-6 text-muted-foreground" />
      </div>
    )
  }

  if (error || !settings) {
    return (
      <div className="px-4 py-8 text-center text-sm text-muted-foreground lg:px-6">
        Impossible de charger les paramètres. Vérifiez que l&apos;API est
        démarrée.
      </div>
    )
  }

  return (
    <Tabs defaultValue="general" className="gap-6">
      <TabsList>
        <TabsTab value="general">
          <HugeiconsIcon icon={MoneyBag02Icon} strokeWidth={2} />
          Frais et délais
        </TabsTab>
        <TabsTab value="plans">
          <HugeiconsIcon icon={CreditCardIcon} strokeWidth={2} />
          Abonnements
        </TabsTab>
      </TabsList>

      <TabsPanel value="general">
        <GeneralSettingsForm settings={settings} onSaved={refresh} />
      </TabsPanel>

      <TabsPanel value="plans">
        <div className="flex flex-col gap-6">
          {plans.map((plan) => (
            <PlanSettingsCard key={plan.id} plan={plan} onSaved={refresh} />
          ))}
        </div>
      </TabsPanel>
    </Tabs>
  )
}

export function ParametresPage() {
  const state = useSettings()

  return (
    <>
      <SiteHeader title="Paramètres" />
      <div className="flex flex-1 flex-col">
        <div className="@container/main flex flex-1 flex-col gap-2">
          <div className="flex flex-col gap-4 px-4 py-4 md:gap-6 md:py-6 lg:px-6">
            <SettingsBody {...state} />
          </div>
        </div>
      </div>
    </>
  )
}
