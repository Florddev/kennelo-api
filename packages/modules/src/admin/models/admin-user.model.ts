import type { UserRole } from "../../users/types/user-roles.type";
import { USER_STATUS, type UserStatusLabel, type UserStatusValue } from "../types/user-status.type";
import type { AdminUserDto } from "./dtos/admin-user.dto";

export class AdminUserModel {
    private constructor(
        public readonly id: string,
        public readonly firstName: string,
        public readonly lastName: string,
        public readonly email: string,
        public readonly phone: string | null,
        public readonly locale: string,
        public readonly avatarUrl: string | null,
        public readonly isIdVerified: boolean,
        public readonly emailVerifiedAt: string | null,
        public readonly status: UserStatusValue | null,
        public readonly isBanned: boolean,
        public readonly banReason: string | null,
        public readonly bannedAt: string | null,
        public readonly bannedUntil: string | null,
        public readonly roles: UserRole[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: AdminUserDto): AdminUserModel {
        return new AdminUserModel(
            dto.id,
            dto.first_name,
            dto.last_name,
            dto.email,
            dto.phone,
            dto.locale,
            dto.avatar_url,
            dto.is_id_verified,
            dto.email_verified_at,
            dto.status ?? null,
            dto.is_banned ?? false,
            dto.ban_reason ?? null,
            dto.banned_at ?? null,
            dto.banned_until ?? null,
            (dto.roles ?? []) as UserRole[],
            dto.created_at,
            dto.updated_at,
        );
    }

    getFullName(): string {
        return `${this.firstName} ${this.lastName}`.trim();
    }

    getInitials(): string {
        return `${this.firstName.charAt(0)}${this.lastName.charAt(0)}`.toUpperCase();
    }

    isEmailVerified(): boolean {
        return this.emailVerifiedAt !== null;
    }

    isActive(): boolean {
        return !this.isBanned && this.status === USER_STATUS.ACTIVE;
    }

    isInactive(): boolean {
        return !this.isBanned && this.status === USER_STATUS.INACTIVE;
    }

    getStatusLabel(): UserStatusLabel {
        if (this.isBanned) return "banned";
        return this.status === USER_STATUS.ACTIVE ? "active" : "inactive";
    }
}
