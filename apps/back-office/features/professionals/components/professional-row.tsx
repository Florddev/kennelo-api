"use client"

import { useState } from "react"
import { flexRender, type Row } from "@tanstack/react-table"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  CancelCircleIcon,
  CheckmarkCircle02Icon,
  MoreHorizontalCircle01Icon,
  SecurityCheckIcon,
} from "@hugeicons/core-free-icons"

import {
  approveProfessional,
  verifyProfessionalCompany,
  type ProfessionalModel,
} from "@workspace/modules/professionals"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { TableCell, TableRow } from "@/components/ui/table"
import { useProfessionalAction } from "@/features/professionals/hooks/use-professional-action"
import { RejectProfessionalDialog } from "@/features/professionals/components/reject-professional-dialog"

type ProfessionalRowProps = {
  row: Row<ProfessionalModel>
  onRefresh: () => Promise<void>
}

export function ProfessionalRow({ row, onRefresh }: ProfessionalRowProps) {
  const professional = row.original
  const { busy, run } = useProfessionalAction()
  const [rejectOpen, setRejectOpen] = useState(false)

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
                    <DropdownMenuItem
                      onClick={() =>
                        run(
                          () => approveProfessional(professional.id),
                          "Professionnel validé.",
                          onRefresh
                        )
                      }
                    >
                      <HugeiconsIcon
                        icon={CheckmarkCircle02Icon}
                        strokeWidth={2}
                      />
                      Valider
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      onClick={() =>
                        run(
                          () => verifyProfessionalCompany(professional.id),
                          "Vérification SIRET effectuée.",
                          onRefresh
                        )
                      }
                    >
                      <HugeiconsIcon icon={SecurityCheckIcon} strokeWidth={2} />
                      Vérifier le SIRET
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      variant="destructive"
                      onClick={() => setRejectOpen(true)}
                    >
                      <HugeiconsIcon icon={CancelCircleIcon} strokeWidth={2} />
                      Refuser
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

      <RejectProfessionalDialog
        professional={professional}
        open={rejectOpen}
        onOpenChange={setRejectOpen}
        onDone={onRefresh}
      />
    </>
  )
}
