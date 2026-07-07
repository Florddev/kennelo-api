"use client"

import {
  Field,
  FieldDescription,
  FieldError,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"

export function SettingNumberField({
  id,
  label,
  hint,
  value,
  onChange,
  error,
  disabled,
  step,
  min,
  max,
}: {
  id: string
  label: string
  hint?: string
  value: string
  onChange: (value: string) => void
  error?: string
  disabled?: boolean
  step?: string
  min?: string
  max?: string
}) {
  return (
    <Field data-invalid={error ? true : undefined}>
      <FieldLabel htmlFor={id}>{label}</FieldLabel>
      <Input
        id={id}
        type="number"
        step={step}
        min={min}
        max={max}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-invalid={error ? true : undefined}
        disabled={disabled}
      />
      {hint ? <FieldDescription>{hint}</FieldDescription> : null}
      <FieldError>{error}</FieldError>
    </Field>
  )
}
