"use client"

import { useCallback, useEffect, useState } from "react"

import {
  getStatsBookings,
  getStatsBusiness,
  getStatsCommunity,
  getStatsFinance,
  getStatsOverview,
  getStatsSearches,
  type StatsBookingsDto,
  type StatsBusinessDto,
  type StatsCommunityDto,
  type StatsFinanceDto,
  type StatsOverviewDto,
  type StatsSearchesDto,
} from "@workspace/modules/stats"

export function useStats() {
  const [overview, setOverview] = useState<StatsOverviewDto | null>(null)
  const [searches, setSearches] = useState<StatsSearchesDto | null>(null)
  const [business, setBusiness] = useState<StatsBusinessDto | null>(null)
  const [finance, setFinance] = useState<StatsFinanceDto | null>(null)
  const [bookings, setBookings] = useState<StatsBookingsDto | null>(null)
  const [community, setCommunity] = useState<StatsCommunityDto | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const [
        overviewData,
        searchesData,
        businessData,
        financeData,
        bookingsData,
        communityData,
      ] = await Promise.all([
        getStatsOverview(),
        getStatsSearches(),
        getStatsBusiness(),
        getStatsFinance(),
        getStatsBookings(),
        getStatsCommunity(),
      ])
      setOverview(overviewData)
      setSearches(searchesData)
      setBusiness(businessData)
      setFinance(financeData)
      setBookings(bookingsData)
      setCommunity(communityData)
    } catch {
      setOverview(null)
      setSearches(null)
      setBusiness(null)
      setFinance(null)
      setBookings(null)
      setCommunity(null)
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  return {
    overview,
    searches,
    business,
    finance,
    bookings,
    community,
    loading,
    error,
    refresh: load,
  }
}
