import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { RecherchesPage } from "./recherches-page"

export const metadata: Metadata = { title: "Recherches — Kennelo Admin" }

export default function Page() {
  return (
    <AuthGuard>
      <RecherchesPage />
    </AuthGuard>
  )
}
