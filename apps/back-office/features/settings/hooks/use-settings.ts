"use client"

import { useCallback, useEffect, useState } from "react"

import {
  getSettings,
  getSubscriptionPlans,
  SettingsModel,
  SubscriptionPlanModel,
} from "@workspace/modules/settings"

export function useSettings() {
  const [settings, setSettings] = useState<SettingsModel | null>(null)
  const [plans, setPlans] = useState<SubscriptionPlanModel[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)
    try {
      const [settingsData, plansData] = await Promise.all([
        getSettings(),
        getSubscriptionPlans(),
      ])
      setSettings(settingsData)
      setPlans(plansData)
    } catch {
      setSettings(null)
      setPlans([])
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  return { settings, plans, loading, error, refresh: load }
}
