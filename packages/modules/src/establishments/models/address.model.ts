import { AddressDto } from "./dtos/establishment.dto";

export class AddressModel {
    private constructor(
        public readonly id: string,
        public readonly line1: string,
        public readonly line2: string | null,
        public readonly postalCode: string,
        public readonly city: string,
        public readonly region: string | null,
        public readonly country: string,
        public readonly latitude: number | null,
        public readonly longitude: number | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: AddressDto): AddressModel {
        return new AddressModel(
            dto.id,
            dto.line1,
            dto.line2,
            dto.postal_code,
            dto.city,
            dto.region,
            dto.country,
            dto.latitude,
            dto.longitude,
            dto.created_at,
            dto.updated_at,
        );
    }

    getFullAddress(): string {
        const parts = [
            this.line1,
            this.line2,
            this.postalCode,
            this.city,
            this.region,
            this.country,
        ];
        return parts.filter(Boolean).join(", ");
    }
}
