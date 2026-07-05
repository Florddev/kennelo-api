"use client"

import { SiteHeader } from "@/components/layout/site-header"
import { MapView } from "@/features/map/components/map-view"

export function CartePage() {
  return (
    <>
      <SiteHeader title="Carte des pensions" />
      <div className="flex flex-1 flex-col py-4 md:py-6">
        <MapView />
      </div>
    </>
  )
}
