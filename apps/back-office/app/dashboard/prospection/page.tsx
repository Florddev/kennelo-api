import type { Metadata } from "next"

import { ProspectionPage } from "./prospection-page"

export const metadata: Metadata = { title: "Prospection — Kennelo Admin" }

export default function Page() {
  return <ProspectionPage />
}
