"use client";

import { useTranslations } from "next-intl";
import { Users } from "lucide-react";

import { Badge } from "@workspace/ui/components/badge";
import type { UserModel } from "@workspace/modules/users";

import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useEstablishment } from "../hooks/use-establishment";
import { EstablishmentDataTable, type DataTableColumn } from "./establishment-data-table";

export function EstablishmentCollaboratorsTable({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const { establishment, isLoading } = useEstablishment(establishmentId);

    const columns: DataTableColumn<UserModel>[] = [
        {
            key: "name",
            header: t("features.my-establishments.manager.collaborators.columns.name"),
            cell: (collaborator) => (
                <div className="flex items-center gap-3 min-w-0">
                    <UserAvatar user={collaborator} className="size-8" size="sm" />
                    <span className="font-medium truncate">{collaborator.getFullName()}</span>
                </div>
            ),
        },
        {
            key: "email",
            header: t("features.my-establishments.manager.collaborators.columns.email"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.email,
        },
        {
            key: "phone",
            header: t("features.my-establishments.manager.collaborators.columns.phone"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.phone ?? "—",
        },
        {
            key: "role",
            header: t("features.my-establishments.manager.collaborators.columns.role"),
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
        <EstablishmentDataTable
            data={establishment?.collaborators ?? []}
            columns={columns}
            isLoading={isLoading}
            getRowKey={(collaborator) => collaborator.id}
            searchPlaceholder={t("features.my-establishments.manager.collaborators.filter")}
            filterRow={(collaborator, query) =>
                collaborator.getFullName().toLowerCase().includes(query) ||
                collaborator.email.toLowerCase().includes(query)
            }
            emptyIcon={Users}
            emptyLabel={t("features.my-establishments.manager.collaborators.empty")}
            renderCount={(count) =>
                t("features.my-establishments.manager.collaborators.count", { count })
            }
        />
    );
}
