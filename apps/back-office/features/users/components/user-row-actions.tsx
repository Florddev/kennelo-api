"use client"

import { useState } from "react"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  Delete02Icon,
  MailValidation01Icon,
  MoreHorizontalCircle01Icon,
  SecurityCheckIcon,
  SquareLock02Icon,
  ToggleOffIcon,
  ToggleOnIcon,
  UserBlock01Icon,
} from "@hugeicons/core-free-icons"

import {
  AdminUserModel,
  forcePasswordReset,
  toggleActiveStatus,
  unbanUser,
  updateUserStatus,
  verifyUserEmail,
} from "@workspace/modules/admin"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

import { BanUserDialog } from "@/features/users/components/ban-user-dialog"
import { DeleteUserDialog } from "@/features/users/components/delete-user-dialog"
import { useUserAction } from "@/features/users/hooks/use-user-action"

type UserRowActionsProps = {
  user: AdminUserModel
  onRefresh: () => Promise<void>
}

export function UserRowActions({ user, onRefresh }: UserRowActionsProps) {
  const { busy, run } = useUserAction()
  const [banOpen, setBanOpen] = useState(false)
  const [deleteOpen, setDeleteOpen] = useState(false)

  return (
    <div className="flex justify-end">
      <DropdownMenu>
        <DropdownMenuTrigger
          render={<Button variant="ghost" size="icon-sm" disabled={busy} />}
        >
          <HugeiconsIcon icon={MoreHorizontalCircle01Icon} strokeWidth={2} />
          <span className="sr-only">Ouvrir les actions</span>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          {user.isBanned ? (
            <DropdownMenuItem
              onClick={() =>
                run(() => unbanUser(user.id), "Utilisateur débanni.", onRefresh)
              }
            >
              <HugeiconsIcon icon={SecurityCheckIcon} strokeWidth={2} />
              Débannir
            </DropdownMenuItem>
          ) : (
            <>
              <DropdownMenuItem
                onClick={() =>
                  run(
                    () =>
                      updateUserStatus(
                        user.id,
                        toggleActiveStatus(user.status)
                      ),
                    user.isActive() ? "Compte désactivé." : "Compte activé.",
                    onRefresh
                  )
                }
              >
                <HugeiconsIcon
                  icon={user.isActive() ? ToggleOffIcon : ToggleOnIcon}
                  strokeWidth={2}
                />
                {user.isActive() ? "Désactiver" : "Activer"}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => setBanOpen(true)}>
                <HugeiconsIcon icon={UserBlock01Icon} strokeWidth={2} />
                Bannir…
              </DropdownMenuItem>
            </>
          )}
          {!user.isEmailVerified() && (
            <DropdownMenuItem
              onClick={() =>
                run(
                  () => verifyUserEmail(user.id),
                  "E-mail vérifié.",
                  onRefresh
                )
              }
            >
              <HugeiconsIcon icon={MailValidation01Icon} strokeWidth={2} />
              Vérifier l&apos;e-mail
            </DropdownMenuItem>
          )}
          <DropdownMenuItem
            onClick={() =>
              run(
                () => forcePasswordReset(user.id),
                "Lien de réinitialisation envoyé.",
                onRefresh
              )
            }
          >
            <HugeiconsIcon icon={SquareLock02Icon} strokeWidth={2} />
            Réinitialiser le mot de passe
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            variant="destructive"
            onClick={() => setDeleteOpen(true)}
          >
            <HugeiconsIcon icon={Delete02Icon} strokeWidth={2} />
            Supprimer…
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <BanUserDialog
        user={user}
        open={banOpen}
        onOpenChange={setBanOpen}
        onDone={onRefresh}
      />
      <DeleteUserDialog
        user={user}
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
        onDone={onRefresh}
      />
    </div>
  )
}
