import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { ProfessionnelsPage } from "./professionnels-page"

export const metadata: Metadata = { title: "Professionnels — Kennelo Admin" }

export default function Page() {
  return (
    <AuthGuard>
      <ProfessionnelsPage />
    </AuthGuard>
  )
}
