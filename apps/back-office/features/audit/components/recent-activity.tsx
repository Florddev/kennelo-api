"use client"

import { useEffect, useState } from "react"

import {
  getAuditActions,
  AdminActionModel,
  type AdminActionType,
} from "@workspace/modules/admin"

import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Spinner } from "@/components/ui/spinner"

const ACTION_LABEL: Record<AdminActionType, string> = {
  ban: "a banni",
  unban: "a débanni",
  force_password_reset: "a réinitialisé le mot de passe de",
  verify_email: "a vérifié l'e-mail de",
  resend_verification: "a renvoyé la vérification à",
  update_status: "a modifié le statut de",
  assign_roles: "a assigné des rôles à",
  remove_role: "a retiré un rôle à",
  review_identity: "a examiné l'identité de",
  delete: "a supprimé",
  impersonate_start: "a incarné",
  impersonate_stop: "a arrêté d'incarner",
  bulk_status: "a modifié le statut en masse",
  bulk_roles: "a modifié les rôles en masse",
  export: "a exporté les utilisateurs",
}

export function RecentActivity() {
  const [actions, setActions] = useState<AdminActionModel[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true

    getAuditActions({ perPage: 8 })
      .then((data) => {
        if (active) setActions(data)
      })
      .catch(() => {
        if (active) setActions([])
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [])

  const renderContent = () => {
    if (loading) {
      return (
        <div className="flex h-24 items-center justify-center">
          <Spinner className="size-5 text-muted-foreground" />
        </div>
      )
    }

    if (actions.length === 0) {
      return (
        <p className="py-6 text-center text-sm text-muted-foreground">
          Aucune action enregistrée.
        </p>
      )
    }

    return (
      <ul className="flex flex-col divide-y">
        {actions.map((action) => {
          const adminName =
            action.getActorName(action.admin) ?? "Un administrateur"
          const targetName = action.getActorName(action.target)

          return (
            <li
              key={action.id}
              className="flex items-center justify-between gap-4 py-3 text-sm"
            >
              <span>
                <span className="font-medium">{adminName}</span>{" "}
                <span className="text-muted-foreground">
                  {ACTION_LABEL[action.action]}
                </span>
                {targetName ? (
                  <>
                    {" "}
                    <span className="font-medium">{targetName}</span>
                  </>
                ) : null}
              </span>
              <span className="shrink-0 text-xs text-muted-foreground">
                {action.createdAt}
              </span>
            </li>
          )
        })}
      </ul>
    )
  }

  return (
    <div className="px-4 lg:px-6">
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Journal d&apos;audit récent</CardTitle>
          <CardDescription>
            Dernières actions effectuées par les administrateurs.
          </CardDescription>
        </CardHeader>
        <div className="px-6 pb-6">{renderContent()}</div>
      </Card>
    </div>
  )
}
