import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { UtilisateursPage } from "./utilisateurs-page"

export const metadata: Metadata = { title: "Utilisateurs — Kennelo Admin" }

export default function Page() {
  return (
    <AuthGuard>
      <UtilisateursPage />
    </AuthGuard>
  )
}
