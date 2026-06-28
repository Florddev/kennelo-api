import type { AnimalTypeDto } from "../../../pets/models/dtos/animal-type.dto";

export type ScannerScanScannerDto = {
    id: string;
    code: string;
    name: string | null;
};

export type ScannerScanPetDto = {
    id: string;
    name: string;
    avatar_url: string | null;
    animal_type: AnimalTypeDto | null;
};

export type ScannerScanDto = {
    id: string;
    microchip_number: string;
    found: boolean;
    scanned_at: string;
    scanner: ScannerScanScannerDto;
    pet: ScannerScanPetDto | null;
};
