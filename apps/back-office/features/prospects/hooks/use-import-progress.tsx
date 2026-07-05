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
import { toast } from "sonner"

import { getProspectImport } from "@workspace/modules/prospects"

import { useNotifications } from "@/features/notifications/hooks/use-notifications"

const STORAGE_KEY = "kennelo.prospect-imports"
const POLL_INTERVAL = 5000

type ImportProgressContextValue = {
  pendingCount: number
  track: (importId: string) => void
}

const ImportProgressContext = createContext<ImportProgressContextValue | null>(
  null
)

function readStored(): string[] {
  if (typeof window === "undefined") return []
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    return raw ? (JSON.parse(raw) as string[]) : []
  } catch {
    return []
  }
}

function writeStored(ids: string[]) {
  if (typeof window === "undefined") return
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ids))
}

export function ImportProgressProvider({ children }: { children: ReactNode }) {
  const [ids, setIds] = useState<string[]>([])
  const idsRef = useRef<string[]>([])
  const { push } = useNotifications()
  const pushRef = useRef(push)
  pushRef.current = push

  useEffect(() => {
    const stored = readStored()
    idsRef.current = stored
    setIds(stored)
  }, [])

  const update = useCallback((next: string[]) => {
    idsRef.current = next
    writeStored(next)
    setIds(next)
  }, [])

  const track = useCallback(
    (importId: string) => {
      if (idsRef.current.includes(importId)) return
      update([...idsRef.current, importId])
    },
    [update]
  )

  useEffect(() => {
    if (ids.length === 0) return

    let cancelled = false

    const poll = async () => {
      const current = idsRef.current
      if (current.length === 0) return

      const results = await Promise.all(
        current.map(async (importId) => {
          try {
            return await getProspectImport(importId)
          } catch {
            return null
          }
        })
      )

      if (cancelled) return

      const stillPending: string[] = []

      results.forEach((result, index) => {
        const importId = current[index]!
        if (!result) {
          stillPending.push(importId)
          return
        }
        if (!result.isFinished()) {
          stillPending.push(importId)
          return
        }
        if (result.status === "completed") {
          const message = `${result.importedCount} prospect(s) importé(s), ${result.skippedCount} ignoré(s).`
          toast.success(
            `Prospection terminée pour « ${result.location} » : ${message}`,
            {
              duration: 8000,
            }
          )
          pushRef.current({
            type: "success",
            title: `Prospection terminée · ${result.location}`,
            body: message,
          })
          if (typeof window !== "undefined") {
            window.dispatchEvent(new CustomEvent("prospects:imported"))
          }
        } else {
          toast.error(
            `La prospection pour « ${result.location} » a échoué. Réessayez plus tard.`
          )
          pushRef.current({
            type: "error",
            title: `Prospection échouée · ${result.location}`,
            body: "L'import n'a pas pu être finalisé. Réessayez plus tard.",
          })
        }
      })

      if (stillPending.length !== current.length) {
        update(stillPending)
      }
    }

    const handle = setInterval(poll, POLL_INTERVAL)
    void poll()

    return () => {
      cancelled = true
      clearInterval(handle)
    }
  }, [ids, update])

  return (
    <ImportProgressContext.Provider value={{ pendingCount: ids.length, track }}>
      {children}
    </ImportProgressContext.Provider>
  )
}

export function useImportProgress(): ImportProgressContextValue {
  const context = useContext(ImportProgressContext)
  if (!context) {
    throw new Error(
      "useImportProgress must be used within ImportProgressProvider"
    )
  }
  return context
}
