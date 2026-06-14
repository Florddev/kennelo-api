import { UserModel } from "../../users/models/user.model";
import type { CollaboratorStatus } from "../types/collaborator-status.type";
import { ActivityModel } from "./activity.model";
import { ActivityRoleModel } from "./activity-role.model";
import type { ActivityCollaboratorDto } from "./dtos/activity-collaborator.dto";

export class ActivityCollaboratorModel {
    private constructor(
        public readonly activityId: string,
        public readonly userId: string,
        public readonly status: CollaboratorStatus,
        public readonly user: UserModel | null,
        public readonly activity: ActivityModel | null,
        public readonly role: ActivityRoleModel | null,
        public readonly invitedAt: string | null,
        public readonly respondedAt: string | null,
    ) {}

    static from(dto: ActivityCollaboratorDto): ActivityCollaboratorModel {
        return new ActivityCollaboratorModel(
            dto.activity_id,
            dto.user_id,
            dto.status,
            dto.user ? UserModel.from(dto.user) : null,
            dto.activity ? ActivityModel.from(dto.activity) : null,
            dto.role ? ActivityRoleModel.from(dto.role) : null,
            dto.invited_at,
            dto.responded_at,
        );
    }

    isPending(): boolean {
        return this.status === "pending";
    }

    isAccepted(): boolean {
        return this.status === "accepted";
    }

    isRefused(): boolean {
        return this.status === "refused";
    }
}
