import { AddressDto } from "../../../address/models/dtos/address.dto";

export type UserDto = {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    avatar_url: string | null;
    is_id_verified: boolean;
    status: string;
    locale: string;
    address: AddressDto | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    two_factor_recovery_codes_count?: number;
    roles: string[];
    created_at: string;
    updated_at: string;
    stripe_account_id?: string | null;
    stripe_customer_id?: string | null;
    stripe_charges_enabled?: boolean;
    stripe_payouts_enabled?: boolean;
    stripe_onboarding_completed?: boolean;
};
