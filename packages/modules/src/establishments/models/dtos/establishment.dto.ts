import { AddressDto } from "../../../address/models/dtos/address.dto";
import { UserDto } from "../../../users/models/dtos/user.dto";
import { EstablishmentImageDto } from "./establishment-image.dto";

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
    is_professional: boolean;
    min_price: number | null;
    animal_types: string[];
    avatar_url: string | null;
    address: AddressDto | null;
    manager?: UserDto | null;
    collaborators?: UserDto[];
    images?: EstablishmentImageDto[];
    created_at: string;
    updated_at: string;
};
