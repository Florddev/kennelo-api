"use client"

import type { TeamMemberPerformanceDto } from "@workspace/modules/stats"

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

export function TeamPerformance({
  team,
}: {
  team: TeamMemberPerformanceDto[]
}) {
  return (
    <Card className="mx-4 lg:mx-6">
      <CardHeader>
        <CardDescription>Équipe commerciale</CardDescription>
        <CardTitle>Performance par membre</CardTitle>
      </CardHeader>
      <CardContent>
        {team.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted-foreground">
            Aucune activité de prospection assignée pour l&apos;instant.
          </p>
        ) : (
          <div className="overflow-hidden rounded-lg border">
            <Table>
              <TableHeader className="bg-muted">
                <TableRow>
                  <TableHead>Membre</TableHead>
                  <TableHead className="text-end">Contacts</TableHead>
                  <TableHead className="text-end">Conversions</TableHead>
                  <TableHead className="text-end">Imports</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {team.map((member, index) => (
                  <TableRow
                    key={member.member}
                    className="animate-in fade-in-0 fill-mode-both"
                    style={{ animationDelay: `${index * 60}ms` }}
                  >
                    <TableCell className="font-medium">
                      {member.member}
                    </TableCell>
                    <TableCell className="text-end tabular-nums">
                      {member.contacts}
                    </TableCell>
                    <TableCell className="text-end tabular-nums">
                      {member.conversions}
                    </TableCell>
                    <TableCell className="text-end tabular-nums">
                      {member.imported}
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
