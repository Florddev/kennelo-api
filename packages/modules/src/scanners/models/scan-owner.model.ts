import type { ScanOwnerDto } from "./dtos/scan-owner.dto";

export class ScanOwnerModel {
    private constructor(
        public readonly id: string,
        public readonly firstName: string,
        public readonly lastName: string,
        public readonly phone: string | null,
        public readonly avatarUrl: string | null,
    ) {}

    static from(dto: ScanOwnerDto): ScanOwnerModel {
        return new ScanOwnerModel(dto.id, dto.first_name, dto.last_name, dto.phone, dto.avatar_url);
    }

    getFullName(): string {
        return `${this.firstName} ${this.lastName}`.trim();
    }

    getInitials(): string {
        return `${this.firstName.charAt(0)}${this.lastName.charAt(0)}`.toUpperCase();
    }
}
