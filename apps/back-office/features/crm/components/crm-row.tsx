"use client"

import { useState } from "react"
import { flexRender, type Row } from "@tanstack/react-table"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  Link01Icon,
  LinkBackwardIcon,
  MapsLocation01Icon,
  MoreHorizontalCircle01Icon,
} from "@hugeicons/core-free-icons"

import {
  unlinkActivityGoogle,
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
import { useCrmAction } from "@/features/crm/hooks/use-crm-action"
import { LinkGoogleDialog } from "@/features/crm/components/link-google-dialog"

type CrmRowProps = {
  row: Row<ProfessionalModel>
  onRefresh: () => Promise<void>
}

export function CrmRow({ row, onRefresh }: CrmRowProps) {
  const activity = row.original
  const { busy, run } = useCrmAction()
  const [linkOpen, setLinkOpen] = useState(false)

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
                    <DropdownMenuItem onClick={() => setLinkOpen(true)}>
                      <HugeiconsIcon icon={Link01Icon} strokeWidth={2} />
                      {activity.isGoogleLinked
                        ? "Modifier la liaison"
                        : "Lier à Google"}
                    </DropdownMenuItem>
                    {activity.isGoogleLinked && activity.googleMapsUrl ? (
                      <DropdownMenuItem
                        render={
                          <a
                            href={activity.googleMapsUrl}
                            target="_blank"
                            rel="noreferrer"
                          />
                        }
                      >
                        <HugeiconsIcon
                          icon={MapsLocation01Icon}
                          strokeWidth={2}
                        />
                        Ouvrir dans Maps
                      </DropdownMenuItem>
                    ) : null}
                    {activity.isGoogleLinked ? (
                      <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          variant="destructive"
                          onClick={() =>
                            run(
                              () => unlinkActivityGoogle(activity.id),
                              "Liaison supprimée.",
                              onRefresh
                            )
                          }
                        >
                          <HugeiconsIcon
                            icon={LinkBackwardIcon}
                            strokeWidth={2}
                          />
                          Délier
                        </DropdownMenuItem>
                      </>
                    ) : null}
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            ) : (
              flexRender(cell.column.columnDef.cell, cell.getContext())
            )}
          </TableCell>
        ))}
      </TableRow>

      <LinkGoogleDialog
        activity={activity}
        open={linkOpen}
        onOpenChange={setLinkOpen}
        onDone={onRefresh}
      />
    </>
  )
}
