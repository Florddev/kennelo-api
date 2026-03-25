import { AddressDto } from "../../../address/models/dtos/address.dto";
import { UserDto } from "../../../users/models/dtos/user.dto";

export type EstablishmentDto = {
    id: string;
    name: string;
    siret: string | null;
    description: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    address_id: string | null;
    timezone: string | null;
    is_active: boolean;
    manager_id: string;
    address: AddressDto | null;
    manager: UserDto;
    collaborators: UserDto[];
    created_at: string;
    updated_at: string;
};
