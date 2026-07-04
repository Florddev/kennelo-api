"use client"

import { AdminUserModel, deleteUser } from "@workspace/modules/admin"

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"

import { useUserAction } from "@/features/users/hooks/use-user-action"

type DeleteUserDialogProps = {
  user: AdminUserModel
  open: boolean
  onOpenChange: (open: boolean) => void
  onDone: () => Promise<void>
}

export function DeleteUserDialog({
  user,
  open,
  onOpenChange,
  onDone,
}: DeleteUserDialogProps) {
  const { busy, run } = useUserAction()

  const onConfirm = () => {
    run(
      () => deleteUser(user.id),
      "Utilisateur supprimé.",
      async () => {
        await onDone()
        onOpenChange(false)
      }
    )
  }

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Supprimer {user.getFullName()} ?</AlertDialogTitle>
          <AlertDialogDescription>
            Cette action est irréversible. Le compte et ses données associées
            seront définitivement supprimés.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Annuler</AlertDialogCancel>
          <AlertDialogAction
            variant="destructive"
            onClick={onConfirm}
            loading={busy}
          >
            Supprimer
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  )
}
