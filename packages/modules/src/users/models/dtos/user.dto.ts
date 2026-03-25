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
    roles: string[];
    created_at: string;
    updated_at: string;
};
