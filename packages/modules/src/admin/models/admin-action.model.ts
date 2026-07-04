import type { AdminActionType } from "../types/admin-action-type.type";
import type { AdminActionActorDto, AdminActionDto } from "./dtos/admin-action.dto";

export type AdminActionActor = {
    id: string;
    firstName: string;
    lastName: string;
    email: string;
};

function actorFrom(dto: AdminActionActorDto | null | undefined): AdminActionActor | null {
    if (!dto) return null;

    return {
        id: dto.id,
        firstName: dto.first_name,
        lastName: dto.last_name,
        email: dto.email,
    };
}

export class AdminActionModel {
    private constructor(
        public readonly id: string,
        public readonly action: AdminActionType,
        public readonly adminId: string | null,
        public readonly targetUserId: string | null,
        public readonly metadata: Record<string, unknown> | null,
        public readonly admin: AdminActionActor | null,
        public readonly target: AdminActionActor | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: AdminActionDto): AdminActionModel {
        return new AdminActionModel(
            dto.id,
            dto.action,
            dto.admin_id,
            dto.target_user_id,
            dto.metadata,
            actorFrom(dto.admin),
            actorFrom(dto.target),
            dto.created_at,
        );
    }

    getActorName(actor: AdminActionActor | null): string | null {
        if (!actor) return null;
        return `${actor.firstName} ${actor.lastName}`.trim();
    }
}
