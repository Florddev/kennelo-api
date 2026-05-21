import { UserModel } from "../../users/models/user.model";
import { AddressModel } from "../../address/models/address.model";
import { EstablishmentImageModel } from "./establishment-image.model";
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
        public readonly isProfessional: boolean,
        public readonly minPrice: number | null,
        public readonly animalTypes: string[],
        public readonly avatarUrl: string | null,
        public readonly rating: number | null,
        public readonly reviewCount: number,
        public readonly distance: number | null,
        public readonly address: AddressModel | null,
        public readonly manager: UserModel | null,
        public readonly collaborators: UserModel[],
        public readonly images: EstablishmentImageModel[],
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
            dto.is_professional ?? dto.siret !== null,
            dto.min_price ?? null,
            dto.animal_types ?? [],
            dto.avatar_url ?? null,
            dto.rating ?? null,
            dto.review_count ?? 0,
            dto.distance ?? null,
            dto.address ? AddressModel.from(dto.address) : null,
            dto.manager ? UserModel.from(dto.manager) : null,
            dto.collaborators ? dto.collaborators.map(UserModel.from) : [],
            dto.images ? dto.images.map(EstablishmentImageModel.from) : [],
            dto.created_at,
            dto.updated_at,
        );
    }

    getAvatarUrl(): string | undefined {
        if (this.avatarUrl) {
            return this.avatarUrl;
        }
        if (this.images[0] != null) {
            return this.images[0].url;
        }
        return undefined;
    }
}
