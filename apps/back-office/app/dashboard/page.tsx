import type { Metadata } from "next"

import { DashboardPage } from "./dashboard-page"

export const metadata: Metadata = {
  title: "Tableau de bord — Kennelo Admin",
}

export default function Page() {
  return <DashboardPage />
}
