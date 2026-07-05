import type { Metadata } from "next"

import { JournalPage } from "./journal-page"

export const metadata: Metadata = {
  title: "Journal d'activité — Kennelo Admin",
}

export default function Page() {
  return <JournalPage />
}
