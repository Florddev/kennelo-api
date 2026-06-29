import type { ScannerDto } from "./dtos/scanner.dto";

export class ScannerModel {
    private constructor(
        public readonly id: string,
        public readonly userId: string,
        public readonly code: string,
        public readonly name: string | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ScannerDto): ScannerModel {
        return new ScannerModel(
            dto.id,
            dto.user_id,
            dto.code,
            dto.name,
            dto.created_at,
            dto.updated_at,
        );
    }

    get displayName(): string {
        return this.name ?? this.code;
    }
}
