import type { Metadata } from "next"

import { ParametresPage } from "./parametres-page"

export const metadata: Metadata = { title: "Paramètres — Kennelo Admin" }

export default function Page() {
  return <ParametresPage />
}
