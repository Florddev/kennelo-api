"use client"

import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"

export function SettingSwitchField({
  id,
  label,
  description,
  checked,
  onCheckedChange,
  disabled,
  whenOn,
  whenOff,
}: {
  id: string
  label: string
  description?: string
  checked: boolean
  onCheckedChange: (checked: boolean) => void
  disabled?: boolean
  whenOn: string
  whenOff: string
}) {
  return (
    <div className="flex items-start justify-between gap-4 rounded-lg border p-4">
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <Label htmlFor={id} className="font-medium">
          {label}
        </Label>
        {description ? (
          <p className="text-sm text-muted-foreground">{description}</p>
        ) : null}
        <p className="mt-1 text-sm">
          <span className="text-muted-foreground">Actuellement : </span>
          <span className={checked ? "text-primary" : "text-foreground"}>
            {checked ? whenOn : whenOff}
          </span>
        </p>
      </div>
      <Switch
        id={id}
        checked={checked}
        onCheckedChange={onCheckedChange}
        disabled={disabled}
        className="shrink-0"
      />
    </div>
  )
}
