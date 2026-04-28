import type { EstablishmentImageDto } from "./dtos/establishment-image.dto";

export class EstablishmentImageModel {
    private constructor(
        public readonly id: string,
        public readonly url: string,
        public readonly order: number,
        public readonly createdAt: string,
    ) {}

    static from(dto: EstablishmentImageDto): EstablishmentImageModel {
        return new EstablishmentImageModel(dto.id, dto.url, dto.order, dto.created_at);
    }
}
