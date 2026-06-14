import type { UserDto } from "../../../users/models/dtos/user.dto";
import type { CollaboratorStatus } from "../../types/collaborator-status.type";
import type { ActivityDto } from "./activity.dto";
import type { ActivityRoleDto } from "./activity-role.dto";

export type ActivityCollaboratorDto = {
    activity_id: string;
    user_id: string;
    status: CollaboratorStatus;
    user?: UserDto | null;
    activity?: ActivityDto | null;
    role?: ActivityRoleDto | null;
    invited_at: string | null;
    responded_at: string | null;
};
