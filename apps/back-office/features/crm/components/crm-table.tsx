"use client"

import { useMemo } from "react"
import {
  flexRender,
  getCoreRowModel,
  getPaginationRowModel,
  useReactTable,
  type ColumnDef,
} from "@tanstack/react-table"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  ArrowLeft01Icon,
  ArrowLeftDoubleIcon,
  ArrowRight01Icon,
  ArrowRightDoubleIcon,
  RefreshIcon,
  Search01Icon,
  StarIcon,
} from "@hugeicons/core-free-icons"

import { type ProfessionalModel } from "@workspace/modules/professionals"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Spinner } from "@/components/ui/spinner"
import { Frame } from "@/components/ui/frame"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

import { CrmRow } from "@/features/crm/components/crm-row"

const LINKED_ITEMS = [
  { label: "Tous", value: "all" },
  { label: "Liés à Google", value: "yes" },
  { label: "Non liés", value: "no" },
]

const PAGE_SIZES = [10, 20, 30, 40, 50]

type CrmTableProps = {
  activities: ProfessionalModel[]
  loading: boolean
  error: boolean
  search: string
  onSearchChange: (value: string) => void
  linked: "" | "yes" | "no"
  onLinkedChange: (value: "" | "yes" | "no") => void
  onRefresh: () => Promise<void>
}

export function CrmTable({
  activities,
  loading,
  error,
  search,
  onSearchChange,
  linked,
  onLinkedChange,
  onRefresh,
}: CrmTableProps) {
  const columns = useMemo<ColumnDef<ProfessionalModel>[]>(
    () => [
      {
        id: "activity",
        header: "Établissement",
        cell: ({ row }) => {
          const activity = row.original
          return (
            <div className="grid gap-0.5 leading-tight">
              <span className="font-medium">{activity.name}</span>
              <span className="text-xs text-muted-foreground">
                {[activity.city, activity.department]
                  .filter(Boolean)
                  .join(" · ") || "—"}
              </span>
            </div>
          )
        },
      },
      {
        id: "siret",
        header: "SIRET",
        cell: ({ row }) =>
          row.original.siret ? (
            <span className="font-mono text-xs">{row.original.siret}</span>
          ) : (
            <span className="text-muted-foreground">—</span>
          ),
      },
      {
        id: "google",
        header: "Liaison Google",
        cell: ({ row }) => {
          const activity = row.original
          if (!activity.isGoogleLinked) {
            return <Badge variant="outline">Non lié</Badge>
          }
          return (
            <div className="flex items-center gap-2">
              <Badge variant="success">Lié</Badge>
              {activity.googleRating !== null ? (
                <span className="inline-flex items-center gap-1 text-sm">
                  <HugeiconsIcon
                    icon={StarIcon}
                    strokeWidth={2}
                    className="size-3.5 text-amber-500"
                  />
                  {activity.googleRating.toFixed(1)}
                  <span className="text-xs text-muted-foreground">
                    ({activity.googleReviewsCount ?? 0})
                  </span>
                </span>
              ) : null}
            </div>
          )
        },
      },
      {
        id: "actions",
        header: "",
        cell: () => null,
      },
    ],
    []
  )

  const table = useReactTable({
    data: activities,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: { pagination: { pageSize: 10 } },
  })

  const renderBody = () => {
    if (loading) {
      return (
        <TableRow>
          <TableCell colSpan={columns.length} className="h-32 text-center">
            <Spinner className="mx-auto size-5 text-muted-foreground" />
          </TableCell>
        </TableRow>
      )
    }

    if (error) {
      return (
        <TableRow>
          <TableCell
            colSpan={columns.length}
            className="h-32 text-center text-muted-foreground"
          >
            Impossible de charger les activités.
          </TableCell>
        </TableRow>
      )
    }

    if (table.getRowModel().rows.length === 0) {
      return (
        <TableRow>
          <TableCell
            colSpan={columns.length}
            className="h-32 text-center text-muted-foreground"
          >
            Aucune activité trouvée.
          </TableCell>
        </TableRow>
      )
    }

    return table
      .getRowModel()
      .rows.map((row) => (
        <CrmRow key={row.id} row={row} onRefresh={onRefresh} />
      ))
  }

  return (
    <div className="flex flex-col gap-4 px-4 lg:px-6">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative w-full sm:max-w-xs">
          <HugeiconsIcon
            icon={Search01Icon}
            strokeWidth={2}
            className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
          />
          <Input
            value={search}
            onChange={(event) => onSearchChange(event.target.value)}
            placeholder="Rechercher une activité…"
            className="ps-9"
          />
        </div>
        <div className="flex items-center gap-2">
          <Select
            value={linked || "all"}
            onValueChange={(value) =>
              onLinkedChange(value === "all" ? "" : (value as "yes" | "no"))
            }
            items={LINKED_ITEMS}
          >
            <SelectTrigger size="sm" className="w-40">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {LINKED_ITEMS.map((item) => (
                  <SelectItem key={item.value} value={item.value}>
                    {item.label}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
          <Button
            variant="outline"
            size="icon"
            onClick={() => onRefresh()}
            aria-label="Rafraîchir"
          >
            <HugeiconsIcon icon={RefreshIcon} strokeWidth={2} />
          </Button>
        </div>
      </div>

      <Frame className="animate-in duration-500 fade-in-0">
        <div className="overflow-hidden rounded-xl border bg-background">
          <Table>
            <TableHeader className="sticky top-0 z-10 bg-muted">
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <TableHead key={header.id} colSpan={header.colSpan}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>{renderBody()}</TableBody>
          </Table>
        </div>
        <div className="flex items-center justify-between p-2">
          <div className="hidden flex-1 text-sm text-muted-foreground lg:flex">
            {activities.length} activité(s)
          </div>
          <div className="flex w-full items-center gap-8 lg:w-fit">
            <div className="hidden items-center gap-2 lg:flex">
              <Label
                htmlFor="crm-rows-per-page"
                className="text-sm font-medium"
              >
                Lignes par page
              </Label>
              <Select
                value={`${table.getState().pagination.pageSize}`}
                onValueChange={(value) => table.setPageSize(Number(value))}
                items={PAGE_SIZES.map((pageSize) => ({
                  label: `${pageSize}`,
                  value: `${pageSize}`,
                }))}
              >
                <SelectTrigger
                  size="sm"
                  className="w-20"
                  id="crm-rows-per-page"
                >
                  <SelectValue />
                </SelectTrigger>
                <SelectContent side="top">
                  <SelectGroup>
                    {PAGE_SIZES.map((pageSize) => (
                      <SelectItem key={pageSize} value={`${pageSize}`}>
                        {pageSize}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex w-fit items-center justify-center text-sm font-medium">
              Page {table.getState().pagination.pageIndex + 1} sur{" "}
              {table.getPageCount() || 1}
            </div>
            <div className="ms-auto flex items-center gap-2 lg:ms-0">
              <Button
                variant="outline"
                size="icon"
                className="hidden lg:flex"
                onClick={() => table.setPageIndex(0)}
                disabled={!table.getCanPreviousPage()}
              >
                <span className="sr-only">Première page</span>
                <HugeiconsIcon icon={ArrowLeftDoubleIcon} strokeWidth={2} />
              </Button>
              <Button
                variant="outline"
                size="icon"
                onClick={() => table.previousPage()}
                disabled={!table.getCanPreviousPage()}
              >
                <span className="sr-only">Page précédente</span>
                <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={2} />
              </Button>
              <Button
                variant="outline"
                size="icon"
                onClick={() => table.nextPage()}
                disabled={!table.getCanNextPage()}
              >
                <span className="sr-only">Page suivante</span>
                <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={2} />
              </Button>
              <Button
                variant="outline"
                size="icon"
                className="hidden lg:flex"
                onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                disabled={!table.getCanNextPage()}
              >
                <span className="sr-only">Dernière page</span>
                <HugeiconsIcon icon={ArrowRightDoubleIcon} strokeWidth={2} />
              </Button>
            </div>
          </div>
        </div>
      </Frame>
    </div>
  )
}
