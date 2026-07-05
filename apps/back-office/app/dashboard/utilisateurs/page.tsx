import type { Metadata } from "next"

import { UtilisateursPage } from "./utilisateurs-page"

export const metadata: Metadata = { title: "Utilisateurs — Kennelo Admin" }

export default function Page() {
  return <UtilisateursPage />
}
