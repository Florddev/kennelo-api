"use client"

import { useState } from "react"
import { flexRender, type Row } from "@tanstack/react-table"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  Delete02Icon,
  Exchange01Icon,
  MoreHorizontalCircle01Icon,
  PencilEdit02Icon,
} from "@hugeicons/core-free-icons"

import {
  deleteProspect,
  reconcileProspect,
  type ProspectModel,
} from "@workspace/modules/prospects"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { TableCell, TableRow } from "@/components/ui/table"
import { useProspectAction } from "@/features/prospects/hooks/use-prospect-action"
import { UpdateStatusDialog } from "@/features/prospects/components/update-status-dialog"

type ProspectRowProps = {
  row: Row<ProspectModel>
  onRefresh: () => Promise<void>
}

export function ProspectRow({ row, onRefresh }: ProspectRowProps) {
  const prospect = row.original
  const { busy, run } = useProspectAction()
  const [statusOpen, setStatusOpen] = useState(false)

  return (
    <>
      <TableRow>
        {row.getVisibleCells().map((cell) => (
          <TableCell key={cell.id}>
            {cell.column.id === "actions" ? (
              <div className="flex justify-end">
                <DropdownMenu>
                  <DropdownMenuTrigger
                    render={
                      <Button variant="ghost" size="icon-sm" disabled={busy} />
                    }
                  >
                    <HugeiconsIcon
                      icon={MoreHorizontalCircle01Icon}
                      strokeWidth={2}
                    />
                    <span className="sr-only">Ouvrir les actions</span>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuItem onClick={() => setStatusOpen(true)}>
                      <HugeiconsIcon icon={PencilEdit02Icon} strokeWidth={2} />
                      Changer le statut
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      onClick={() =>
                        run(
                          () => reconcileProspect(prospect.id),
                          "Rapprochement effectué.",
                          onRefresh
                        )
                      }
                    >
                      <HugeiconsIcon icon={Exchange01Icon} strokeWidth={2} />
                      Rapprocher (SIRET)
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      variant="destructive"
                      onClick={() =>
                        run(
                          () => deleteProspect(prospect.id),
                          "Prospect supprimé.",
                          onRefresh
                        )
                      }
                    >
                      <HugeiconsIcon icon={Delete02Icon} strokeWidth={2} />
                      Supprimer
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            ) : (
              flexRender(cell.column.columnDef.cell, cell.getContext())
            )}
          </TableCell>
        ))}
      </TableRow>

      <UpdateStatusDialog
        prospect={prospect}
        open={statusOpen}
        onOpenChange={setStatusOpen}
        onDone={onRefresh}
      />
    </>
  )
}
