"use client"

import { HugeiconsIcon } from "@hugeicons/react"
import { MoreHorizontalCircle01Icon } from "@hugeicons/core-free-icons"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

import type { UserActionEntry } from "@/features/users/hooks/use-user-row-actions"

type UserActionsDropdownProps = {
  actions: UserActionEntry[]
  busy: boolean
}

export function UserActionsDropdown({
  actions,
  busy,
}: UserActionsDropdownProps) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        render={<Button variant="ghost" size="icon-sm" disabled={busy} />}
      >
        <HugeiconsIcon icon={MoreHorizontalCircle01Icon} strokeWidth={2} />
        <span className="sr-only">Ouvrir les actions</span>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        {actions.map((entry) =>
          entry.type === "separator" ? (
            <DropdownMenuSeparator key={entry.key} />
          ) : (
            <DropdownMenuItem
              key={entry.key}
              variant={entry.destructive ? "destructive" : undefined}
              onClick={entry.onSelect}
            >
              <HugeiconsIcon icon={entry.icon} strokeWidth={2} />
              {entry.label}
            </DropdownMenuItem>
          )
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
