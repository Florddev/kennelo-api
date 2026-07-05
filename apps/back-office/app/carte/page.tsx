import type { Metadata } from "next"

import { AuthGuard } from "@/features/auth/components/auth-guard"

import { CartePage } from "./carte-page"

export const metadata: Metadata = { title: "Carte — Kennelo Admin" }

export default function Page() {
  return (
    <AuthGuard>
      <CartePage />
    </AuthGuard>
  )
}
