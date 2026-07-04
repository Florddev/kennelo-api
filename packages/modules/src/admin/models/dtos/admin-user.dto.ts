import type { UserStatusValue } from "../../types/user-status.type";

export type AdminUserDto = {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    locale: string;
    avatar_url: string | null;
    is_id_verified: boolean;
    email_verified_at: string | null;
    status?: UserStatusValue | null;
    is_banned?: boolean | null;
    ban_reason?: string | null;
    banned_at?: string | null;
    banned_until?: string | null;
    roles?: string[];
    created_at: string;
    updated_at: string;
};
