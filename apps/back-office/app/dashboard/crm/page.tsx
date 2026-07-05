import type { Metadata } from "next"

import { CrmPage } from "./crm-page"

export const metadata: Metadata = { title: "CRM — Kennelo Admin" }

export default function Page() {
  return <CrmPage />
}
