"use client"

import { useState } from "react"
import { useTheme } from "next-themes"

import type {
  ProspectFeatureCollection,
  ProspectFeatureProperties,
} from "@workspace/modules/prospects"
import {
  Map,
  MapClusterLayer,
  MapControls,
  MapPopup,
} from "@workspace/ui/components/mapcn"

const FRANCE_CENTER: [number, number] = [2.4, 46.6]

type SelectedPoint = {
  coordinates: [number, number]
  properties: ProspectFeatureProperties
}

export function ProspectMap({ data }: { data: ProspectFeatureCollection }) {
  const { resolvedTheme } = useTheme()
  const [selected, setSelected] = useState<SelectedPoint | null>(null)

  return (
    <Map
      center={FRANCE_CENTER}
      zoom={5}
      theme={resolvedTheme === "dark" ? "dark" : "light"}
      className="absolute inset-0"
    >
      <MapControls position="top-right" showZoom />
      <MapClusterLayer<ProspectFeatureProperties>
        data={data}
        onPointClick={(feature, coordinates) =>
          setSelected({ coordinates, properties: feature.properties })
        }
      />
      {selected ? (
        <MapPopup
          longitude={selected.coordinates[0]}
          latitude={selected.coordinates[1]}
          closeButton
          onClose={() => setSelected(null)}
        >
          <div className="flex flex-col gap-1 p-1">
            <span className="font-medium">{selected.properties.name}</span>
            {selected.properties.address ? (
              <span className="text-xs text-muted-foreground">
                {selected.properties.address}
              </span>
            ) : null}
            {selected.properties.phone ? (
              <span className="text-xs">{selected.properties.phone}</span>
            ) : null}
            {selected.properties.google_rating !== null ? (
              <span className="text-xs">
                Note Google : {selected.properties.google_rating}
              </span>
            ) : null}
            <span className="text-xs">
              {selected.properties.is_registered
                ? "Inscrit sur Kennelo"
                : "Non inscrit"}
            </span>
          </div>
        </MapPopup>
      ) : null}
    </Map>
  )
}
