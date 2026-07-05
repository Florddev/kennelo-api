"use client"

import { useCallback, useState } from "react"
import { toast } from "sonner"

export function useProfessionalAction() {
  const [busy, setBusy] = useState(false)

  const run = useCallback(
    async (
      action: () => Promise<unknown>,
      successMessage: string,
      onDone?: () => void | Promise<void>
    ) => {
      setBusy(true)
      try {
        await action()
        toast.success(successMessage)
        await onDone?.()
      } catch (error) {
        toast.error(error instanceof Error ? error.message : "Action échouée.")
      } finally {
        setBusy(false)
      }
    },
    []
  )

  return { busy, run }
}
