"use client"

import { useState } from "react"

import {
  updateProspectStatus,
  type ProspectModel,
  type ProspectStatusValue,
} from "@workspace/modules/prospects"

import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogClose,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogPanel,
  DialogPopup,
  DialogTitle,
} from "@/components/ui/dialog"
import { Field, FieldLabel } from "@/components/ui/field"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { useProspectAction } from "@/features/prospects/hooks/use-prospect-action"
import { PROSPECT_STATUS_OPTIONS } from "@/features/prospects/components/prospect-status-badge"

type UpdateStatusDialogProps = {
  prospect: ProspectModel
  open: boolean
  onOpenChange: (open: boolean) => void
  onDone: () => Promise<void>
}

const STATUS_ITEMS = PROSPECT_STATUS_OPTIONS.filter(
  (option) => option.value !== ""
)

export function UpdateStatusDialog({
  prospect,
  open,
  onOpenChange,
  onDone,
}: UpdateStatusDialogProps) {
  const { busy, run } = useProspectAction()
  const [status, setStatus] = useState<ProspectStatusValue>(prospect.status)

  const onSubmit = () => {
    run(
      () => updateProspectStatus(prospect.id, status),
      "Statut mis à jour.",
      async () => {
        await onDone()
        onOpenChange(false)
      }
    )
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogPopup className="max-w-md">
        <DialogHeader>
          <DialogTitle>Changer le statut</DialogTitle>
          <DialogDescription>{prospect.name}</DialogDescription>
        </DialogHeader>
        <DialogPanel>
          <Field>
            <FieldLabel htmlFor={`status-${prospect.id}`}>Statut</FieldLabel>
            <Select
              value={status}
              onValueChange={(value) => setStatus(value as ProspectStatusValue)}
              items={STATUS_ITEMS}
            >
              <SelectTrigger id={`status-${prospect.id}`} className="w-full">
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
          </Field>
        </DialogPanel>
        <DialogFooter>
          <DialogClose render={<Button variant="outline" />}>
            Annuler
          </DialogClose>
          <Button onClick={onSubmit} loading={busy}>
            Enregistrer
          </Button>
        </DialogFooter>
      </DialogPopup>
    </Dialog>
  )
}
