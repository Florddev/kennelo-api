"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Users } from "lucide-react";

import { Badge } from "@workspace/ui/components/badge";
import { Input } from "@workspace/ui/components/input";
import type { UserModel } from "@workspace/modules/users";

import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useEstablishment } from "../hooks/use-establishment";
import { EstablishmentDataTable, type DataTableColumn } from "./establishment-data-table";
import { EstablishmentPageHeader } from "./establishment-page-header";

export function EstablishmentCollaboratorsTable({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const [search, setSearch] = useState("");
    const { establishment, isLoading } = useEstablishment(establishmentId);

    const columns: DataTableColumn<UserModel>[] = [
        {
            key: "name",
            header: t("features.establishments.manager.collaborators.columns.name"),
            cell: (collaborator) => (
                <div className="flex items-center gap-3 min-w-0">
                    <UserAvatar user={collaborator} className="size-8" size="sm" />
                    <span className="font-medium truncate">{collaborator.getFullName()}</span>
                </div>
            ),
        },
        {
            key: "email",
            header: t("features.establishments.manager.collaborators.columns.email"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.email,
        },
        {
            key: "phone",
            header: t("features.establishments.manager.collaborators.columns.phone"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.phone ?? "—",
        },
        {
            key: "role",
            header: t("features.establishments.manager.collaborators.columns.role"),
            cell: (collaborator) =>
                collaborator.roles.length > 0 ? (
                    <div className="flex flex-wrap gap-1.5">
                        {collaborator.roles.map((role) => (
                            <Badge key={role} variant="secondary" size="sm">
                                {role}
                            </Badge>
                        ))}
                    </div>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
    ];

    return (
        <div className="flex flex-col gap-6">
            <EstablishmentPageHeader>
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder={t("features.establishments.manager.collaborators.filter")}
                    className="w-64"
                />
            </EstablishmentPageHeader>
            <EstablishmentDataTable
                data={establishment?.collaborators ?? []}
                columns={columns}
                isLoading={isLoading}
                getRowKey={(collaborator) => collaborator.id}
                search={search}
                filterRow={(collaborator, query) =>
                    collaborator.getFullName().toLowerCase().includes(query) ||
                    collaborator.email.toLowerCase().includes(query)
                }
                emptyIcon={Users}
                emptyLabel={t("features.establishments.manager.collaborators.empty")}
                renderCount={(count) =>
                    t("features.establishments.manager.collaborators.count", { count })
                }
            />
        </div>
    );
}
