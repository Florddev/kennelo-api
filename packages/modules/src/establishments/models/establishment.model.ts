import { UserModel } from "../../users/models/user.model";
import { AddressModel } from "./address.model";
import { EstablishmentDto } from "./dtos/establishment.dto";

export class EstablishmentModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly siret: string | null,
        public readonly description: string | null,
        public readonly phone: string | null,
        public readonly email: string | null,
        public readonly website: string | null,
        public readonly addressId: string | null,
        public readonly timezone: string | null,
        public readonly isActive: boolean,
        public readonly managerId: string,
        public readonly address: AddressModel | null,
        public readonly manager: UserModel,
        public readonly collaborators: UserModel[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: EstablishmentDto): EstablishmentModel {
        return new EstablishmentModel(
            dto.id,
            dto.name,
            dto.siret,
            dto.description,
            dto.phone,
            dto.email,
            dto.website,
            dto.address_id,
            dto.timezone,
            dto.is_active,
            dto.manager_id,
            dto.address ? AddressModel.from(dto.address) : null,
            UserModel.from(dto.manager),
            dto.collaborators.map(UserModel.from),
            dto.created_at,
            dto.updated_at,
        );
    }
}
