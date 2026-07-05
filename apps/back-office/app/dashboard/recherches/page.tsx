import type { Metadata } from "next"

import { RecherchesPage } from "./recherches-page"

export const metadata: Metadata = { title: "Recherches — Kennelo Admin" }

export default function Page() {
  return <RecherchesPage />
}
