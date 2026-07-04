import type { Metadata } from "next"

import { LoginForm } from "@/features/auth/components/login-form"

export const metadata: Metadata = {
  title: "Connexion — Kennelo Admin",
}

export default function Page() {
  return <LoginForm />
}
