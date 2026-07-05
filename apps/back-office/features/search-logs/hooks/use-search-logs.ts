"use client"

import { useCallback, useEffect, useState } from "react"

import { getSearchLogs, SearchLogModel } from "@workspace/modules/search-logs"

const PER_PAGE = 100

export function useSearchLogs() {
  const [logs, setLogs] = useState<SearchLogModel[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [search, setSearch] = useState("")
  const [department, setDepartment] = useState("")

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const data = await getSearchLogs({
        search: search || undefined,
        department: department || undefined,
        perPage: PER_PAGE,
      })
      setLogs(data)
    } catch {
      setLogs([])
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [search, department])

  useEffect(() => {
    const handle = setTimeout(load, 300)
    return () => clearTimeout(handle)
  }, [load])

  return {
    logs,
    loading,
    error,
    search,
    setSearch,
    department,
    setDepartment,
    refresh: load,
  }
}
