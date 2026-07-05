"use client"

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
  type ReactNode,
} from "react"

export type NotificationType = "success" | "error" | "info"

export type AppNotification = {
  id: string
  type: NotificationType
  title: string
  body?: string
  createdAt: number
  read: boolean
}

type NotificationsContextValue = {
  notifications: AppNotification[]
  unreadCount: number
  push: (input: {
    type: NotificationType
    title: string
    body?: string
  }) => void
  markAsRead: (id: string) => void
  markAllRead: () => void
  remove: (id: string) => void
  clearAll: () => void
}

const NotificationsContext = createContext<NotificationsContextValue | null>(
  null
)

const STORAGE_KEY = "kennelo.notifications"
const MAX_NOTIFICATIONS = 50

function readStored(): AppNotification[] {
  if (typeof window === "undefined") return []
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    return raw ? (JSON.parse(raw) as AppNotification[]) : []
  } catch {
    return []
  }
}

function writeStored(items: AppNotification[]) {
  if (typeof window === "undefined") return
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
}

function makeId(): string {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID()
  }
  return `${Date.now()}-${Math.round(Math.random() * 1e9)}`
}

export function NotificationsProvider({ children }: { children: ReactNode }) {
  const [notifications, setNotifications] = useState<AppNotification[]>([])
  const ref = useRef<AppNotification[]>([])

  useEffect(() => {
    const stored = readStored()
    ref.current = stored
    setNotifications(stored)
  }, [])

  const commit = useCallback((next: AppNotification[]) => {
    const trimmed = next.slice(0, MAX_NOTIFICATIONS)
    ref.current = trimmed
    writeStored(trimmed)
    setNotifications(trimmed)
  }, [])

  const push = useCallback<NotificationsContextValue["push"]>(
    (input) => {
      const notification: AppNotification = {
        id: makeId(),
        type: input.type,
        title: input.title,
        body: input.body,
        createdAt: Date.now(),
        read: false,
      }
      commit([notification, ...ref.current])
    },
    [commit]
  )

  const markAsRead = useCallback(
    (id: string) => {
      commit(
        ref.current.map((item) =>
          item.id === id ? { ...item, read: true } : item
        )
      )
    },
    [commit]
  )

  const markAllRead = useCallback(() => {
    commit(ref.current.map((item) => ({ ...item, read: true })))
  }, [commit])

  const remove = useCallback(
    (id: string) => {
      commit(ref.current.filter((item) => item.id !== id))
    },
    [commit]
  )

  const clearAll = useCallback(() => {
    commit([])
  }, [commit])

  const unreadCount = notifications.filter((item) => !item.read).length

  return (
    <NotificationsContext.Provider
      value={{
        notifications,
        unreadCount,
        push,
        markAsRead,
        markAllRead,
        remove,
        clearAll,
      }}
    >
      {children}
    </NotificationsContext.Provider>
  )
}

export function useNotifications(): NotificationsContextValue {
  const context = useContext(NotificationsContext)
  if (!context) {
    throw new Error(
      "useNotifications must be used within NotificationsProvider"
    )
  }
  return context
}
