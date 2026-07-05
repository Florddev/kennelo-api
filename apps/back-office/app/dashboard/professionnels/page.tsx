import type { Metadata } from "next"

import { ProfessionnelsPage } from "./professionnels-page"

export const metadata: Metadata = { title: "Professionnels — Kennelo Admin" }

export default function Page() {
  return <ProfessionnelsPage />
}
