import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { ProspectionPage } from "./prospection-page"

export const metadata: Metadata = { title: "Prospection — Kennelo Admin" }

export default function Page() {
  return (
    <AuthGuard>
      <ProspectionPage />
    </AuthGuard>
  )
}
