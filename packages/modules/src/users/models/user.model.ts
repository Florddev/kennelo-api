import { AddressModel } from "../../address/models/address.model";
import type { UserRole } from "../types/user-roles.type";
import { UserDto } from "./dtos/user.dto";

export class UserModel {
    private constructor(
        public readonly id: string,
        public readonly firstName: string,
        public readonly lastName: string,
        public readonly email: string,
        public readonly phone: string | null,
        public readonly avatarUrl: string | null,
        public readonly isIdVerified: boolean,
        public readonly status: string,
        public readonly locale: string,
        public readonly address: AddressModel | null,
        public readonly emailVerifiedAt: string | null,
        public readonly twoFactorEnabled: boolean,
        public readonly roles: UserRole[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
        public readonly stripeAccountId: string | null,
        public readonly stripeCustomerId: string | null,
        public readonly stripeChargesEnabled: boolean,
        public readonly stripePayoutsEnabled: boolean,
        public readonly stripeOnboardingCompleted: boolean,
    ) {}

    static from(dto: UserDto): UserModel {
        return new UserModel(
            dto.id,
            dto.first_name,
            dto.last_name,
            dto.email,
            dto.phone,
            dto.avatar_url,
            dto.is_id_verified,
            dto.status,
            dto.locale,
            dto.address ? AddressModel.from(dto.address) : null,
            dto.email_verified_at,
            dto.two_factor_enabled ?? false,
            (dto.roles ?? []) as UserRole[],
            dto.created_at,
            dto.updated_at,
            dto.stripe_account_id ?? null,
            dto.stripe_customer_id ?? null,
            dto.stripe_charges_enabled ?? false,
            dto.stripe_payouts_enabled ?? false,
            dto.stripe_onboarding_completed ?? false,
        );
    }

    getFullName(): string {
        return `${this.firstName} ${this.lastName}`;
    }

    getInitials(): string {
        return `${this.firstName.charAt(0)}${this.lastName.charAt(0)}`.toUpperCase();
    }

    isEmailVerified(): boolean {
        return this.emailVerifiedAt !== null;
    }

    hasRoles(roles: UserRole[]): boolean {
        return roles.every((role) => this.roles.includes(role));
    }

    hasAnyRoles(roles: UserRole[]): boolean {
        return roles.some((role) => this.roles.includes(role));
    }
}
