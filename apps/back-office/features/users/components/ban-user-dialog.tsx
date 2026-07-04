"use client"

import { useState } from "react"
import { toast } from "sonner"

import { AdminUserModel, banUser } from "@workspace/modules/admin"

import { Button } from "@/components/ui/button"
import { Textarea } from "@/components/ui/textarea"
import { Field, FieldLabel } from "@/components/ui/field"
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

import { useUserAction } from "@/features/users/hooks/use-user-action"

type BanUserDialogProps = {
  user: AdminUserModel
  open: boolean
  onOpenChange: (open: boolean) => void
  onDone: () => Promise<void>
}

export function BanUserDialog({
  user,
  open,
  onOpenChange,
  onDone,
}: BanUserDialogProps) {
  const { busy, run } = useUserAction()
  const [reason, setReason] = useState("")

  const onSubmit = () => {
    if (!reason.trim()) {
      toast.error("Un motif est requis.")
      return
    }

    run(
      () => banUser(user.id, { reason: reason.trim() }),
      "Utilisateur banni.",
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
          <DialogTitle>Bannir {user.getFullName()}</DialogTitle>
          <DialogDescription>
            L&apos;utilisateur perdra immédiatement l&apos;accès à son compte.
            Indiquez le motif du bannissement.
          </DialogDescription>
        </DialogHeader>
        <DialogPanel>
          <Field>
            <FieldLabel htmlFor={`ban-reason-${user.id}`}>Motif</FieldLabel>
            <Textarea
              id={`ban-reason-${user.id}`}
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              placeholder="Comportement abusif, fraude…"
              rows={3}
            />
          </Field>
        </DialogPanel>
        <DialogFooter>
          <DialogClose render={<Button variant="outline" />}>
            Annuler
          </DialogClose>
          <Button variant="destructive" onClick={onSubmit} loading={busy}>
            Bannir
          </Button>
        </DialogFooter>
      </DialogPopup>
    </Dialog>
  )
}
