"use client"

import dynamic from "next/dynamic"

import { type ProspectStatusValue } from "@workspace/modules/prospects"

import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { PROSPECT_STATUS_OPTIONS } from "@/features/prospects/components/prospect-status-badge"
import { useProspectMap } from "@/features/map/hooks/use-prospect-map"

const ProspectMap = dynamic(
  () =>
    import("@/features/map/components/prospect-map").then(
      (mod) => mod.ProspectMap
    ),
  { ssr: false }
)

const STATUS_ITEMS = PROSPECT_STATUS_OPTIONS.map((option) => ({
  label: option.label,
  value: option.value === "" ? "all" : option.value,
}))

const REGISTERED_ITEMS = [
  { label: "Tous", value: "all" },
  { label: "Inscrits", value: "yes" },
  { label: "Non inscrits", value: "no" },
]

export function MapView() {
  const {
    data,
    loading,
    error,
    status,
    setStatus,
    registered,
    setRegistered,
    refresh,
  } = useProspectMap()

  return (
    <div className="flex flex-1 flex-col gap-4 px-4 lg:px-6">
      <div className="flex flex-wrap items-center gap-2">
        <Select
          value={status || "all"}
          onValueChange={(value) =>
            setStatus(value === "all" ? "" : (value as ProspectStatusValue))
          }
          items={STATUS_ITEMS}
        >
          <SelectTrigger size="sm" className="w-44">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              {STATUS_ITEMS.map((item) => (
                <SelectItem key={item.value} value={item.value}>
                  {item.label}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
        <Select
          value={registered || "all"}
          onValueChange={(value) =>
            setRegistered(value === "all" ? "" : (value as "yes" | "no"))
          }
          items={REGISTERED_ITEMS}
        >
          <SelectTrigger size="sm" className="w-36">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              {REGISTERED_ITEMS.map((item) => (
                <SelectItem key={item.value} value={item.value}>
                  {item.label}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
        <span className="text-sm text-muted-foreground">
          {data.features.length} établissement(s)
        </span>
        <Button
          variant="outline"
          size="sm"
          className="ms-auto"
          onClick={() => refresh()}
        >
          Rafraîchir
        </Button>
      </div>

      <div className="relative flex-1 animate-in overflow-hidden rounded-xl border duration-500 fade-in-0 zoom-in-95">
        {loading ? (
          <div className="absolute inset-0 z-10 flex items-center justify-center bg-background/60">
            <Spinner className="size-6 text-muted-foreground" />
          </div>
        ) : null}
        {error ? (
          <div className="absolute inset-0 z-10 flex items-center justify-center text-sm text-muted-foreground">
            Impossible de charger la carte.
          </div>
        ) : null}
        <ProspectMap data={data} />
      </div>
    </div>
  )
}
