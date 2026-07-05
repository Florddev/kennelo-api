"use client"

import { useCallback, useEffect, useState } from "react"

import {
  getProfessionals,
  ProfessionalModel,
  type ActivityStatusValue,
} from "@workspace/modules/professionals"

const PER_PAGE = 100

export function useProfessionals() {
  const [professionals, setProfessionals] = useState<ProfessionalModel[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [search, setSearch] = useState("")
  const [status, setStatus] = useState<ActivityStatusValue | "">("")

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const data = await getProfessionals({
        search: search || undefined,
        status: status || undefined,
        perPage: PER_PAGE,
      })
      setProfessionals(data)
    } catch {
      setProfessionals([])
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [search, status])

  useEffect(() => {
    const handle = setTimeout(load, 300)
    return () => clearTimeout(handle)
  }, [load])

  return {
    professionals,
    loading,
    error,
    search,
    setSearch,
    status,
    setStatus,
    refresh: load,
  }
}
