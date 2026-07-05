"use client"

import { useCallback, useEffect, useState } from "react"

import {
  getProspects,
  ProspectModel,
  type ProspectSortBy,
  type ProspectSortDir,
  type ProspectStatusValue,
} from "@workspace/modules/prospects"

const PER_PAGE = 100

export function useProspects() {
  const [prospects, setProspects] = useState<ProspectModel[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [search, setSearch] = useState("")
  const [status, setStatus] = useState<ProspectStatusValue | "">("")
  const [registered, setRegistered] = useState<"" | "yes" | "no">("")
  const [sortBy] = useState<ProspectSortBy>("created_at")
  const [sortDir] = useState<ProspectSortDir>("desc")

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const data = await getProspects({
        search: search || undefined,
        status: status || undefined,
        registered: registered === "" ? undefined : registered === "yes",
        sortBy,
        sortDirection: sortDir,
        perPage: PER_PAGE,
      })
      setProspects(data)
    } catch {
      setProspects([])
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [search, status, registered, sortBy, sortDir])

  useEffect(() => {
    const handle = setTimeout(load, 300)
    return () => clearTimeout(handle)
  }, [load])

  useEffect(() => {
    const onImported = () => void load()
    window.addEventListener("prospects:imported", onImported)
    return () => window.removeEventListener("prospects:imported", onImported)
  }, [load])

  return {
    prospects,
    loading,
    error,
    search,
    setSearch,
    status,
    setStatus,
    registered,
    setRegistered,
    refresh: load,
  }
}
