import type { ActivityPermission } from "../../types/activity-permission.type";

export type ActivityRoleDto = {
    id: string;
    activity_id: string;
    name: string;
    permissions: ActivityPermission[];
    created_at: string;
    updated_at: string;
};
