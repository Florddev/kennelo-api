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
  SecurityCheckIcon,
} from "@hugeicons/core-free-icons"

import {
  type ActivityStatusValue,
  type ProfessionalModel,
} from "@workspace/modules/professionals"

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

import {
  ACTIVITY_STATUS_OPTIONS,
  ActivityStatusBadge,
} from "@/features/professionals/components/activity-status-badge"
import { ProfessionalRow } from "@/features/professionals/components/professional-row"

const PAGE_SIZES = [10, 20, 30, 40, 50]

type ProfessionalsTableProps = {
  professionals: ProfessionalModel[]
  loading: boolean
  error: boolean
  search: string
  onSearchChange: (value: string) => void
  status: ActivityStatusValue | ""
  onStatusChange: (value: ActivityStatusValue | "") => void
  onRefresh: () => Promise<void>
}

export function ProfessionalsTable({
  professionals,
  loading,
  error,
  search,
  onSearchChange,
  status,
  onStatusChange,
  onRefresh,
}: ProfessionalsTableProps) {
  const columns = useMemo<ColumnDef<ProfessionalModel>[]>(
    () => [
      {
        id: "professional",
        header: "Professionnel",
        cell: ({ row }) => {
          const pro = row.original
          return (
            <div className="grid gap-0.5 leading-tight">
              <span className="font-medium">{pro.name}</span>
              <span className="text-xs text-muted-foreground">
                {pro.manager
                  ? `${pro.manager.firstName} ${pro.manager.lastName}`
                  : "—"}
                {pro.city ? ` · ${pro.city}` : ""}
              </span>
            </div>
          )
        },
      },
      {
        id: "siret",
        header: "SIRET",
        cell: ({ row }) => {
          const pro = row.original
          if (!pro.siret) {
            return <span className="text-muted-foreground">—</span>
          }
          return (
            <span className="inline-flex items-center gap-1.5 font-mono text-xs">
              {pro.siret}
              {pro.isVerified() ? (
                <HugeiconsIcon
                  icon={SecurityCheckIcon}
                  strokeWidth={2}
                  className="size-3.5 text-green-500"
                />
              ) : null}
            </span>
          )
        },
      },
      {
        id: "status",
        header: "Statut",
        cell: ({ row }) => <ActivityStatusBadge status={row.original.status} />,
      },
      {
        id: "type",
        header: "Type",
        cell: ({ row }) =>
          row.original.isProfessional ? (
            <Badge variant="info">Pro</Badge>
          ) : (
            <Badge variant="outline">Particulier</Badge>
          ),
      },
      {
        id: "createdAt",
        header: "Inscription",
        cell: ({ row }) => (
          <span className="text-xs text-muted-foreground">
            {row.original.createdAt}
          </span>
        ),
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
    data: professionals,
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
            Impossible de charger les professionnels.
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
            Aucun professionnel trouvé.
          </TableCell>
        </TableRow>
      )
    }

    return table
      .getRowModel()
      .rows.map((row) => (
        <ProfessionalRow key={row.id} row={row} onRefresh={onRefresh} />
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
            placeholder="Rechercher un professionnel…"
            className="ps-9"
          />
        </div>
        <div className="flex items-center gap-2">
          <Select
            value={status || "all"}
            onValueChange={(value) =>
              onStatusChange(
                value === "all" ? "" : (value as ActivityStatusValue)
              )
            }
            items={ACTIVITY_STATUS_OPTIONS}
          >
            <SelectTrigger size="sm" className="w-44">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {ACTIVITY_STATUS_OPTIONS.map((item) => (
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
            {professionals.length} professionnel(s)
          </div>
          <div className="flex w-full items-center gap-8 lg:w-fit">
            <div className="hidden items-center gap-2 lg:flex">
              <Label
                htmlFor="pro-rows-per-page"
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
                  id="pro-rows-per-page"
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
