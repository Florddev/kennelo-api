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
} from "@hugeicons/core-free-icons"

import { type SearchLogModel } from "@workspace/modules/search-logs"

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

const PAGE_SIZES = [10, 20, 30, 40, 50]

function formatFilters(filters: Record<string, unknown> | null): string {
  if (!filters) return "—"
  const entries = Object.entries(filters)
  if (entries.length === 0) return "—"
  return entries.map(([key, value]) => `${key}: ${String(value)}`).join(" · ")
}

type SearchLogsTableProps = {
  logs: SearchLogModel[]
  loading: boolean
  error: boolean
  search: string
  onSearchChange: (value: string) => void
  department: string
  onDepartmentChange: (value: string) => void
  onRefresh: () => Promise<void>
}

export function SearchLogsTable({
  logs,
  loading,
  error,
  search,
  onSearchChange,
  department,
  onDepartmentChange,
  onRefresh,
}: SearchLogsTableProps) {
  const departmentItems = useMemo(() => {
    const departments = Array.from(
      new Set(
        logs
          .map((log) => log.department)
          .filter((value): value is string => Boolean(value))
      )
    ).sort()
    return [
      { label: "Tous les départements", value: "all" },
      ...departments.map((dept) => ({ label: dept, value: dept })),
    ]
  }, [logs])

  const columns = useMemo<ColumnDef<SearchLogModel>[]>(
    () => [
      {
        id: "location",
        header: "Localisation",
        cell: ({ row }) =>
          row.original.location ?? (
            <span className="text-muted-foreground">—</span>
          ),
      },
      {
        id: "department",
        header: "Département",
        cell: ({ row }) =>
          row.original.department ? (
            <Badge variant="outline">{row.original.department}</Badge>
          ) : (
            <span className="text-muted-foreground">—</span>
          ),
      },
      {
        id: "filters",
        header: "Filtres",
        cell: ({ row }) => (
          <span className="text-xs text-muted-foreground">
            {formatFilters(row.original.filters)}
          </span>
        ),
      },
      {
        id: "results",
        header: "Résultats",
        cell: ({ row }) => (
          <span className="tabular-nums">{row.original.resultsCount}</span>
        ),
      },
      {
        id: "user",
        header: "Utilisateur",
        cell: ({ row }) =>
          row.original.userEmail ?? (
            <span className="text-muted-foreground">Anonyme</span>
          ),
      },
      {
        id: "createdAt",
        header: "Date",
        cell: ({ row }) => (
          <span className="text-xs text-muted-foreground">
            {row.original.createdAt}
          </span>
        ),
      },
    ],
    []
  )

  const table = useReactTable({
    data: logs,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: { pagination: { pageSize: 20 } },
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
            Impossible de charger les recherches.
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
            Aucune recherche enregistrée.
          </TableCell>
        </TableRow>
      )
    }

    return table.getRowModel().rows.map((row) => (
      <TableRow key={row.id}>
        {row.getVisibleCells().map((cell) => (
          <TableCell key={cell.id}>
            {flexRender(cell.column.columnDef.cell, cell.getContext())}
          </TableCell>
        ))}
      </TableRow>
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
            placeholder="Rechercher une localisation…"
            className="ps-9"
          />
        </div>
        <div className="flex items-center gap-2">
          <Select
            value={department || "all"}
            onValueChange={(value) =>
              onDepartmentChange(value === "all" ? "" : String(value))
            }
            items={departmentItems}
          >
            <SelectTrigger size="sm" className="w-48">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {departmentItems.map((item) => (
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
            {logs.length} recherche(s)
          </div>
          <div className="flex w-full items-center gap-8 lg:w-fit">
            <div className="hidden items-center gap-2 lg:flex">
              <Label
                htmlFor="search-rows-per-page"
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
                  id="search-rows-per-page"
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
