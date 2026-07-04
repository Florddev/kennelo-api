import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"
import { DashboardPage } from "./dashboard-page"

export const metadata: Metadata = {
  title: "Tableau de bord — Kennelo Admin",
}

export default function Page() {
  return (
    <AuthGuard>
      <DashboardPage />
    </AuthGuard>
  )
}
