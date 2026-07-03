"use client"

import { useState, type ComponentProps } from "react"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  Delete02Icon,
  MailValidation01Icon,
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

import { useUserAction } from "@/features/users/hooks/use-user-action"

type ActionIcon = ComponentProps<typeof HugeiconsIcon>["icon"]

export type UserActionEntry =
  | { type: "separator"; key: string }
  | {
      type: "item"
      key: string
      icon: ActionIcon
      label: string
      destructive?: boolean
      onSelect: () => void
    }

export function useUserRowActions(
  user: AdminUserModel,
  onRefresh: () => Promise<void>
) {
  const { busy, run } = useUserAction()
  const [banOpen, setBanOpen] = useState(false)
  const [deleteOpen, setDeleteOpen] = useState(false)

  const actions: UserActionEntry[] = []

  if (user.isBanned) {
    actions.push({
      type: "item",
      key: "unban",
      icon: SecurityCheckIcon,
      label: "Débannir",
      onSelect: () =>
        run(() => unbanUser(user.id), "Utilisateur débanni.", onRefresh),
    })
  } else {
    actions.push({
      type: "item",
      key: "status",
      icon: user.isActive() ? ToggleOffIcon : ToggleOnIcon,
      label: user.isActive() ? "Désactiver" : "Activer",
      onSelect: () =>
        run(
          () => updateUserStatus(user.id, toggleActiveStatus(user.status)),
          user.isActive() ? "Compte désactivé." : "Compte activé.",
          onRefresh
        ),
    })
    actions.push({
      type: "item",
      key: "ban",
      icon: UserBlock01Icon,
      label: "Bannir…",
      onSelect: () => setBanOpen(true),
    })
  }

  if (!user.isEmailVerified()) {
    actions.push({
      type: "item",
      key: "verify",
      icon: MailValidation01Icon,
      label: "Vérifier l'e-mail",
      onSelect: () =>
        run(() => verifyUserEmail(user.id), "E-mail vérifié.", onRefresh),
    })
  }

  actions.push({
    type: "item",
    key: "reset",
    icon: SquareLock02Icon,
    label: "Réinitialiser le mot de passe",
    onSelect: () =>
      run(
        () => forcePasswordReset(user.id),
        "Lien de réinitialisation envoyé.",
        onRefresh
      ),
  })
  actions.push({ type: "separator", key: "sep-delete" })
  actions.push({
    type: "item",
    key: "delete",
    icon: Delete02Icon,
    label: "Supprimer…",
    destructive: true,
    onSelect: () => setDeleteOpen(true),
  })

  return { actions, busy, banOpen, setBanOpen, deleteOpen, setDeleteOpen }
}
