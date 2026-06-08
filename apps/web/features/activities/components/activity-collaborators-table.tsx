"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Users } from "lucide-react";

import { Badge } from "@workspace/ui/components/badge";
import { Input } from "@workspace/ui/components/input";
import type { UserModel } from "@workspace/modules/users";

import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useActivity } from "../hooks/use-activity";
import { ActivityDataTable, type DataTableColumn } from "./activity-data-table";
import { ActivityPageHeader } from "./activity-page-header";

export function ActivityCollaboratorsTable({ activityId }: { activityId: string }) {
    const t = useTranslations();
    const [search, setSearch] = useState("");
    const { activity, isLoading } = useActivity(activityId);

    const columns: DataTableColumn<UserModel>[] = [
        {
            key: "name",
            header: t("features.activities.manager.collaborators.columns.name"),
            cell: (collaborator) => (
                <div className="flex items-center gap-3 min-w-0">
                    <UserAvatar user={collaborator} className="size-8" size="sm" />
                    <span className="font-medium truncate">{collaborator.getFullName()}</span>
                </div>
            ),
        },
        {
            key: "email",
            header: t("features.activities.manager.collaborators.columns.email"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.email,
        },
        {
            key: "phone",
            header: t("features.activities.manager.collaborators.columns.phone"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.phone ?? "—",
        },
        {
            key: "role",
            header: t("features.activities.manager.collaborators.columns.role"),
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
            <ActivityPageHeader>
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder={t("features.activities.manager.collaborators.filter")}
                    className="w-64"
                />
            </ActivityPageHeader>
            <ActivityDataTable
                data={activity?.collaborators ?? []}
                columns={columns}
                isLoading={isLoading}
                getRowKey={(collaborator) => collaborator.id}
                search={search}
                filterRow={(collaborator, query) =>
                    collaborator.getFullName().toLowerCase().includes(query) ||
                    collaborator.email.toLowerCase().includes(query)
                }
                emptyIcon={Users}
                emptyLabel={t("features.activities.manager.collaborators.empty")}
                renderCount={(count) =>
                    t("features.activities.manager.collaborators.count", { count })
                }
            />
        </div>
    );
}
