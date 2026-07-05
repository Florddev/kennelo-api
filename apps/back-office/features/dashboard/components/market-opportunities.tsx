"use client"

import type { MarketCoverageDto } from "@workspace/modules/stats"

import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

export function MarketOpportunities({
  coverage,
}: {
  coverage: MarketCoverageDto[]
}) {
  return (
    <Card className="mx-4 lg:mx-6">
      <CardHeader>
        <CardDescription>Opportunités commerciales</CardDescription>
        <CardTitle>Départements à fort potentiel</CardTitle>
        <p className="text-sm text-muted-foreground">
          Forte demande utilisateurs, peu de professionnels inscrits : zones à
          démarcher en priorité.
        </p>
      </CardHeader>
      <CardContent>
        {coverage.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted-foreground">
            Pas encore assez de données de recherche pour identifier des
            opportunités.
          </p>
        ) : (
          <div className="overflow-hidden rounded-lg border">
            <Table>
              <TableHeader className="bg-muted">
                <TableRow>
                  <TableHead>Département</TableHead>
                  <TableHead className="text-end">Recherches</TableHead>
                  <TableHead className="text-end">Pros inscrits</TableHead>
                  <TableHead className="text-end">Potentiel</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {coverage.map((row, index) => (
                  <TableRow
                    key={row.department}
                    className="animate-in fade-in-0 fill-mode-both"
                    style={{ animationDelay: `${index * 60}ms` }}
                  >
                    <TableCell className="font-medium">
                      {row.department}
                    </TableCell>
                    <TableCell className="text-end tabular-nums">
                      {row.searches}
                    </TableCell>
                    <TableCell className="text-end tabular-nums">
                      {row.professionals}
                    </TableCell>
                    <TableCell className="text-end">
                      <Badge
                        variant={
                          row.professionals === 0 ? "destructive" : "warning"
                        }
                      >
                        {row.professionals === 0 ? "Vierge" : "À renforcer"}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
