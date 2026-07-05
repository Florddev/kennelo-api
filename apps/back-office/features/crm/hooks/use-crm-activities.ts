"use client"

import { useCallback, useEffect, useMemo, useState } from "react"

import {
  getProfessionals,
  ProfessionalModel,
} from "@workspace/modules/professionals"

const PER_PAGE = 100

export function useCrmActivities() {
  const [activities, setActivities] = useState<ProfessionalModel[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [search, setSearch] = useState("")
  const [linked, setLinked] = useState<"" | "yes" | "no">("")

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const data = await getProfessionals({
        search: search || undefined,
        status: "approved",
        perPage: PER_PAGE,
      })
      setActivities(data)
    } catch {
      setActivities([])
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [search])

  useEffect(() => {
    const handle = setTimeout(load, 300)
    return () => clearTimeout(handle)
  }, [load])

  const filtered = useMemo(() => {
    if (linked === "") return activities
    return activities.filter((activity) =>
      linked === "yes" ? activity.isGoogleLinked : !activity.isGoogleLinked
    )
  }, [activities, linked])

  return {
    activities: filtered,
    allActivities: activities,
    loading,
    error,
    search,
    setSearch,
    linked,
    setLinked,
    refresh: load,
  }
}
