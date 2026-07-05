import type { Metadata } from "next"

import { CartePage } from "./carte-page"

export const metadata: Metadata = { title: "Carte — Kennelo Admin" }

export default function Page() {
  return <CartePage />
}
