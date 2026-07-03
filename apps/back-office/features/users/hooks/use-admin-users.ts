"use client"

import { useCallback, useEffect, useState } from "react"

import {
  getAdminUsers,
  AdminUserModel,
  type AdminUserSortBy,
  type AdminUserSortDir,
} from "@workspace/modules/admin"

const PER_PAGE = 100

export function useAdminUsers() {
  const [users, setUsers] = useState<AdminUserModel[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [search, setSearch] = useState("")
  const [role, setRole] = useState("")
  const [sortBy] = useState<AdminUserSortBy>("created_at")
  const [sortDir] = useState<AdminUserSortDir>("desc")

  const load = useCallback(async () => {
    setLoading(true)
    setError(false)

    try {
      const data = await getAdminUsers({
        search: search || undefined,
        role: role || undefined,
        sortBy,
        sortDir,
        perPage: PER_PAGE,
      })
      setUsers(data)
    } catch {
      setUsers([])
      setError(true)
    } finally {
      setLoading(false)
    }
  }, [search, role, sortBy, sortDir])

  useEffect(() => {
    const handle = setTimeout(load, 300)
    return () => clearTimeout(handle)
  }, [load])

  return {
    users,
    loading,
    error,
    search,
    setSearch,
    role,
    setRole,
    refresh: load,
  }
}
