import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { JournalPage } from "./journal-page"

export const metadata: Metadata = {
  title: "Journal d'activité — Kennelo Admin",
}

export default function Page() {
  return (
    <AuthGuard>
      <JournalPage />
    </AuthGuard>
  )
}
