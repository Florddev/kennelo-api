"use client"

import { flexRender, type Row } from "@tanstack/react-table"
import { HugeiconsIcon } from "@hugeicons/react"

import { AdminUserModel } from "@workspace/modules/admin"

import { TableCell, TableRow } from "@/components/ui/table"
import {
  ContextMenu,
  ContextMenuItem,
  ContextMenuPopup,
  ContextMenuSeparator,
  ContextMenuTrigger,
} from "@/components/ui/context-menu"

import { BanUserDialog } from "@/features/users/components/ban-user-dialog"
import { DeleteUserDialog } from "@/features/users/components/delete-user-dialog"
import { UserActionsDropdown } from "@/features/users/components/user-actions-dropdown"
import { useUserRowActions } from "@/features/users/hooks/use-user-row-actions"

type UserRowProps = {
  row: Row<AdminUserModel>
  onRefresh: () => Promise<void>
}

export function UserRow({ row, onRefresh }: UserRowProps) {
  const user = row.original
  const { actions, busy, banOpen, setBanOpen, deleteOpen, setDeleteOpen } =
    useUserRowActions(user, onRefresh)

  return (
    <ContextMenu>
      <ContextMenuTrigger render={<TableRow />}>
        {row.getVisibleCells().map((cell) => (
          <TableCell key={cell.id}>
            {cell.column.id === "actions" ? (
              <div className="flex justify-end">
                <UserActionsDropdown actions={actions} busy={busy} />
              </div>
            ) : (
              flexRender(cell.column.columnDef.cell, cell.getContext())
            )}
          </TableCell>
        ))}
      </ContextMenuTrigger>
      <ContextMenuPopup className="w-56">
        {actions.map((entry) =>
          entry.type === "separator" ? (
            <ContextMenuSeparator key={entry.key} />
          ) : (
            <ContextMenuItem
              key={entry.key}
              variant={entry.destructive ? "destructive" : "default"}
              onClick={entry.onSelect}
            >
              <HugeiconsIcon icon={entry.icon} strokeWidth={2} />
              {entry.label}
            </ContextMenuItem>
          )
        )}
      </ContextMenuPopup>

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
    </ContextMenu>
  )
}
