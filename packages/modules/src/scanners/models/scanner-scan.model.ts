import { AnimalTypeModel } from "../../pets/models/animal-type.model";
import type { ScannerScanDto } from "./dtos/scanner-scan.dto";

export type ScannerScanPet = {
    id: string;
    name: string;
    avatarUrl: string | null;
    animalType: AnimalTypeModel | null;
};

export class ScannerScanModel {
    private constructor(
        public readonly id: string,
        public readonly microchipNumber: string,
        public readonly found: boolean,
        public readonly scannedAt: string,
        public readonly scannerId: string,
        public readonly scannerCode: string,
        public readonly scannerName: string | null,
        public readonly pet: ScannerScanPet | null,
    ) {}

    static from(dto: ScannerScanDto): ScannerScanModel {
        return new ScannerScanModel(
            dto.id,
            dto.microchip_number,
            dto.found,
            dto.scanned_at,
            dto.scanner.id,
            dto.scanner.code,
            dto.scanner.name,
            dto.pet
                ? {
                      id: dto.pet.id,
                      name: dto.pet.name,
                      avatarUrl: dto.pet.avatar_url,
                      animalType: dto.pet.animal_type
                          ? AnimalTypeModel.from(dto.pet.animal_type)
                          : null,
                  }
                : null,
        );
    }

    get scannerDisplayName(): string {
        return this.scannerName ?? this.scannerCode;
    }
}
