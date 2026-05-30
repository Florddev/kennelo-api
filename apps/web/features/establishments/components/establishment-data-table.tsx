"use client";

import { useMemo, useState } from "react";
import type { LucideIcon } from "lucide-react";

import { Input } from "@workspace/ui/components/input";
import { Skeleton } from "@workspace/ui/components/skeleton";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@workspace/ui/components/table";
import { cn } from "@workspace/ui/lib/utils";

export type DataTableColumn<T> = {
    key: string;
    header: string;
    headClassName?: string;
    cellClassName?: string;
    cell: (row: T) => React.ReactNode;
};

type EstablishmentDataTableProps<T> = {
    data: T[];
    columns: DataTableColumn<T>[];
    isLoading: boolean;
    getRowKey: (row: T) => string;
    searchPlaceholder: string;
    filterRow: (row: T, query: string) => boolean;
    emptyIcon: LucideIcon;
    emptyLabel: string;
    renderCount: (count: number) => string;
};

export function EstablishmentDataTable<T>({
    data,
    columns,
    isLoading,
    getRowKey,
    searchPlaceholder,
    filterRow,
    emptyIcon: EmptyIcon,
    emptyLabel,
    renderCount,
}: EstablishmentDataTableProps<T>) {
    const [search, setSearch] = useState("");

    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return data;
        return data.filter((row) => filterRow(row, query));
    }, [data, search, filterRow]);

    const colCount = columns.length;

    let body: React.ReactNode;
    if (isLoading) {
        body = Array.from({ length: 4 }).map((_, index) => (
            <TableRow key={index}>
                <TableCell colSpan={colCount}>
                    <Skeleton className="h-6 w-full" />
                </TableCell>
            </TableRow>
        ));
    } else if (filtered.length === 0) {
        body = (
            <TableRow>
                <TableCell colSpan={colCount}>
                    <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                        <div className="flex items-center justify-center size-12 rounded-full bg-muted">
                            <EmptyIcon className="size-6 text-muted-foreground" />
                        </div>
                        <p className="text-sm text-muted-foreground">{emptyLabel}</p>
                    </div>
                </TableCell>
            </TableRow>
        );
    } else {
        body = filtered.map((row) => (
            <TableRow key={getRowKey(row)}>
                {columns.map((column) => (
                    <TableCell key={column.key} className={cn(column.cellClassName)}>
                        {column.cell(row)}
                    </TableCell>
                ))}
            </TableRow>
        ));
    }

    return (
        <div className="flex flex-col gap-4">
            <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={searchPlaceholder}
                className="max-w-sm"
            />

            <div className="rounded-2xl border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHead key={column.key} className={cn(column.headClassName)}>
                                    {column.header}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>{body}</TableBody>
                </Table>
            </div>

            {!isLoading && filtered.length > 0 && (
                <p className="text-xs text-muted-foreground">{renderCount(filtered.length)}</p>
            )}
        </div>
    );
}
