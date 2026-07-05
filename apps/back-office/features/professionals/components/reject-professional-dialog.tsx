"use client"

import { useState } from "react"
import { toast } from "sonner"

import {
  rejectProfessional,
  type ProfessionalModel,
} from "@workspace/modules/professionals"

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
import { Textarea } from "@/components/ui/textarea"
import { useProfessionalAction } from "@/features/professionals/hooks/use-professional-action"

type RejectProfessionalDialogProps = {
  professional: ProfessionalModel
  open: boolean
  onOpenChange: (open: boolean) => void
  onDone: () => Promise<void>
}

export function RejectProfessionalDialog({
  professional,
  open,
  onOpenChange,
  onDone,
}: RejectProfessionalDialogProps) {
  const { busy, run } = useProfessionalAction()
  const [reason, setReason] = useState("")

  const onSubmit = () => {
    if (!reason.trim()) {
      toast.error("Un motif est requis.")
      return
    }

    run(
      () => rejectProfessional(professional.id, reason.trim()),
      "Professionnel refusé.",
      async () => {
        await onDone()
        onOpenChange(false)
        setReason("")
      }
    )
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogPopup className="max-w-md">
        <DialogHeader>
          <DialogTitle>Refuser {professional.name}</DialogTitle>
          <DialogDescription>
            Indiquez le motif du refus. Le professionnel restera inactif.
          </DialogDescription>
        </DialogHeader>
        <DialogPanel>
          <Field>
            <FieldLabel htmlFor={`reject-reason-${professional.id}`}>
              Motif
            </FieldLabel>
            <Textarea
              id={`reject-reason-${professional.id}`}
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              rows={3}
              placeholder="SIRET invalide, informations incomplètes…"
            />
          </Field>
        </DialogPanel>
        <DialogFooter>
          <DialogClose render={<Button variant="outline" />}>
            Annuler
          </DialogClose>
          <Button variant="destructive" onClick={onSubmit} loading={busy}>
            Refuser
          </Button>
        </DialogFooter>
      </DialogPopup>
    </Dialog>
  )
}
