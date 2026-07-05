import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { CrmPage } from "./crm-page"

export const metadata: Metadata = { title: "CRM — Kennelo Admin" }

export default function Page() {
  return (
    <AuthGuard>
      <CrmPage />
    </AuthGuard>
  )
}
