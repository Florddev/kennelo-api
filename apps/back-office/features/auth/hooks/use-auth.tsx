"use client"

import {
  createContext,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react"
import { useRouter } from "next/navigation"
import { api, LocalStorageService } from "@workspace/common"
import {
  authService,
  getCurrentUser,
  logoutUser,
  UserModel,
} from "@workspace/modules/users"

type AuthContextValue = {
  user: UserModel | null
  isLoading: boolean
  isAuthenticated: boolean
  logout: () => Promise<void>
  refreshUser: () => Promise<UserModel | null>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<UserModel | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const router = useRouter()

  useState(() => {
    authService.configure(new LocalStorageService())
    api.setTokenGetter(() => authService.getAccessToken())
  })

  const loadUser = async (): Promise<UserModel | null> => {
    if (!(await authService.isAuthenticated())) {
      setIsAuthenticated(false)
      setUser(null)
      setIsLoading(false)
      return null
    }

    try {
      const currentUser = await getCurrentUser()

      if (!currentUser || !currentUser.hasRoles(["admin"])) {
        await authService.clearTokens()
        setIsAuthenticated(false)
        setUser(null)
        return null
      }

      setIsAuthenticated(true)
      setUser(currentUser)
      return currentUser
    } catch {
      await authService.clearTokens()
      setIsAuthenticated(false)
      setUser(null)
      return null
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    loadUser()
  }, [])

  const logout = async () => {
    try {
      await logoutUser()
    } finally {
      setIsAuthenticated(false)
      setUser(null)
      router.push("/login")
    }
  }

  const refreshUser = async (): Promise<UserModel | null> => loadUser()

  const value: AuthContextValue = {
    user,
    isLoading,
    isAuthenticated,
    logout,
    refreshUser,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider")
  }

  return context
}
