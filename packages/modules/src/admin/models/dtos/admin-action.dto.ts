import type { AdminActionType } from "../../types/admin-action-type.type";

export type AdminActionActorDto = {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
};

export type AdminActionDto = {
    id: string;
    action: AdminActionType;
    admin_id: string | null;
    target_user_id: string | null;
    metadata: Record<string, unknown> | null;
    admin?: AdminActionActorDto | null;
    target?: AdminActionActorDto | null;
    created_at: string;
};
