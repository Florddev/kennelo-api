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
  Call02Icon,
  LinkSquare02Icon,
  RefreshIcon,
  Search01Icon,
  StarIcon,
} from "@hugeicons/core-free-icons"

import {
  type ProspectModel,
  type ProspectStatusValue,
} from "@workspace/modules/prospects"

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

import { ImportProspectsDialog } from "@/features/prospects/components/import-prospects-dialog"
import { ProspectRow } from "@/features/prospects/components/prospect-row"
import {
  PROSPECT_STATUS_OPTIONS,
  ProspectStatusBadge,
} from "@/features/prospects/components/prospect-status-badge"

const REGISTERED_ITEMS = [
  { label: "Tous", value: "all" },
  { label: "Inscrits", value: "yes" },
  { label: "Non inscrits", value: "no" },
]

const STATUS_ITEMS = PROSPECT_STATUS_OPTIONS.map((option) => ({
  label: option.label,
  value: option.value === "" ? "all" : option.value,
}))

const PAGE_SIZES = [10, 20, 30, 40, 50]

type ProspectsTableProps = {
  prospects: ProspectModel[]
  loading: boolean
  error: boolean
  search: string
  onSearchChange: (value: string) => void
  status: ProspectStatusValue | ""
  onStatusChange: (value: ProspectStatusValue | "") => void
  registered: "" | "yes" | "no"
  onRegisteredChange: (value: "" | "yes" | "no") => void
  onRefresh: () => Promise<void>
}

export function ProspectsTable({
  prospects,
  loading,
  error,
  search,
  onSearchChange,
  status,
  onStatusChange,
  registered,
  onRegisteredChange,
  onRefresh,
}: ProspectsTableProps) {
  const columns = useMemo<ColumnDef<ProspectModel>[]>(
    () => [
      {
        id: "prospect",
        header: "Établissement",
        cell: ({ row }) => {
          const prospect = row.original
          return (
            <div className="grid gap-0.5 leading-tight">
              <span className="font-medium">{prospect.name}</span>
              <span className="text-xs text-muted-foreground">
                {[prospect.address, prospect.postalCode, prospect.city]
                  .filter(Boolean)
                  .join(", ") || "—"}
              </span>
              {prospect.category ? (
                <Badge variant="secondary" size="sm" className="mt-1 w-fit">
                  {prospect.category}
                </Badge>
              ) : null}
            </div>
          )
        },
      },
      {
        id: "contact",
        header: "Contact",
        cell: ({ row }) => {
          const prospect = row.original
          return (
            <div className="grid gap-1 leading-tight">
              {prospect.phone ? (
                <a
                  href={`tel:${prospect.phone}`}
                  className="inline-flex items-center gap-1 text-sm hover:underline"
                >
                  <HugeiconsIcon
                    icon={Call02Icon}
                    strokeWidth={2}
                    className="size-3.5 text-muted-foreground"
                  />
                  {prospect.phone}
                </a>
              ) : (
                <span className="text-muted-foreground">—</span>
              )}
              {prospect.website ? (
                <a
                  href={prospect.website}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                >
                  <HugeiconsIcon
                    icon={LinkSquare02Icon}
                    strokeWidth={2}
                    className="size-3.5"
                  />
                  Site web
                </a>
              ) : null}
            </div>
          )
        },
      },
      {
        id: "rating",
        header: "Note Google",
        cell: ({ row }) => {
          const prospect = row.original
          if (prospect.googleRating === null) {
            return <span className="text-muted-foreground">—</span>
          }
          return (
            <span className="inline-flex items-center gap-1">
              <HugeiconsIcon
                icon={StarIcon}
                strokeWidth={2}
                className="size-3.5 text-amber-500"
              />
              {prospect.googleRating.toFixed(1)}
              <span className="text-xs text-muted-foreground">
                ({prospect.googleReviewsCount ?? 0} avis)
              </span>
            </span>
          )
        },
      },
      {
        id: "status",
        header: "Statut",
        cell: ({ row }) => <ProspectStatusBadge status={row.original.status} />,
      },
      {
        id: "registered",
        header: "Kennelo",
        cell: ({ row }) =>
          row.original.isRegistered ? (
            <Badge variant="success">Inscrit</Badge>
          ) : (
            <Badge variant="outline">Non inscrit</Badge>
          ),
      },
      {
        id: "createdAt",
        header: "Ajouté le",
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
    data: prospects,
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
            Impossible de charger les prospects.
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
            Aucun prospect trouvé.
          </TableCell>
        </TableRow>
      )
    }

    return table
      .getRowModel()
      .rows.map((row) => (
        <ProspectRow key={row.id} row={row} onRefresh={onRefresh} />
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
            placeholder="Rechercher un nom ou une ville…"
            className="ps-9"
          />
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Select
            value={status || "all"}
            onValueChange={(value) =>
              onStatusChange(
                value === "all" ? "" : (value as ProspectStatusValue)
              )
            }
            items={STATUS_ITEMS}
          >
            <SelectTrigger size="sm" className="w-44">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {STATUS_ITEMS.map((item) => (
                  <SelectItem key={item.value} value={item.value}>
                    {item.label}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
          <Select
            value={registered || "all"}
            onValueChange={(value) =>
              onRegisteredChange(value === "all" ? "" : (value as "yes" | "no"))
            }
            items={REGISTERED_ITEMS}
          >
            <SelectTrigger size="sm" className="w-36">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {REGISTERED_ITEMS.map((item) => (
                  <SelectItem key={item.value} value={item.value}>
                    {item.label}
                  </SelectItem>
                ))}
              </SelectGroup>
            </SelectContent>
          </Select>
          <ImportProspectsDialog />
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
            {prospects.length} prospect(s)
          </div>
          <div className="flex w-full items-center gap-8 lg:w-fit">
            <div className="hidden items-center gap-2 lg:flex">
              <Label
                htmlFor="prospect-rows-per-page"
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
                  id="prospect-rows-per-page"
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
