import type { ActivityPermission } from "../types/activity-permission.type";
import type { ActivityRoleDto } from "./dtos/activity-role.dto";

export class ActivityRoleModel {
    private constructor(
        public readonly id: string,
        public readonly activityId: string,
        public readonly name: string,
        public readonly permissions: ActivityPermission[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ActivityRoleDto): ActivityRoleModel {
        return new ActivityRoleModel(
            dto.id,
            dto.activity_id,
            dto.name,
            dto.permissions ?? [],
            dto.created_at,
            dto.updated_at,
        );
    }

    has(permission: ActivityPermission): boolean {
        return this.permissions.includes(permission);
    }
}
