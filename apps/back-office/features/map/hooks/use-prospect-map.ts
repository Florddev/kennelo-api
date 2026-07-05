"use client"

import { useCallback, useEffect, useState } from "react"

import {
  getProspectMap,
  type ProspectFeatureCollection,
  type ProspectStatusValue,
} from "@workspace/modules/prospects"

const EMPTY: ProspectFeatureCollection = {
  type: "FeatureCollection",
  features: [],
}

export function useProspectMap() {
  const [data, setData] = useState<ProspectFeatureCollection>(EMPTY)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [status, setStatus] = useState<ProspectStatusValue | "">("")
  const [registered, setRegistered] = useState<"" | "yes" | "no">("")

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const collection = await getProspectMap({
        status: status || undefined,
        registered: registered === "" ? undefined : registered === "yes",
      })
      setData(collection)
    } catch {
      setData(EMPTY)
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [status, registered])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    const onImported = () => void load()
    window.addEventListener("prospects:imported", onImported)
    return () => window.removeEventListener("prospects:imported", onImported)
  }, [load])

  return {
    data,
    loading,
    error,
    status,
    setStatus,
    registered,
    setRegistered,
    refresh: load,
  }
}
