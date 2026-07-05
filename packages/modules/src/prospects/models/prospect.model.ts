import type { ProspectDto } from "./dtos/prospect.dto";
import { PROSPECT_STATUS, type ProspectStatusValue } from "../types/prospect-status.type";

export class ProspectModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly address: string | null,
        public readonly city: string | null,
        public readonly postalCode: string | null,
        public readonly department: string | null,
        public readonly region: string | null,
        public readonly country: string,
        public readonly latitude: number | null,
        public readonly longitude: number | null,
        public readonly phone: string | null,
        public readonly website: string | null,
        public readonly googleRating: number | null,
        public readonly googleReviewsCount: number | null,
        public readonly googlePlaceId: string | null,
        public readonly category: string | null,
        public readonly animalTypes: string[] | null,
        public readonly services: string[] | null,
        public readonly siret: string | null,
        public readonly siren: string | null,
        public readonly apeCode: string | null,
        public readonly status: ProspectStatusValue,
        public readonly source: string,
        public readonly isRegistered: boolean,
        public readonly assignedTo: string | null,
        public readonly kenneloActivityId: string | null,
        public readonly reconciledAt: string | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ProspectDto): ProspectModel {
        return new ProspectModel(
            dto.id,
            dto.name,
            dto.address ?? null,
            dto.city ?? null,
            dto.postal_code ?? null,
            dto.department ?? null,
            dto.region ?? null,
            dto.country,
            dto.latitude ?? null,
            dto.longitude ?? null,
            dto.phone ?? null,
            dto.website ?? null,
            dto.google_rating ?? null,
            dto.google_reviews_count ?? null,
            dto.google_place_id ?? null,
            dto.category ?? null,
            dto.animal_types ?? null,
            dto.services ?? null,
            dto.siret ?? null,
            dto.siren ?? null,
            dto.ape_code ?? null,
            dto.status,
            dto.source,
            dto.is_registered ?? false,
            dto.assigned_to ?? null,
            dto.kennelo_activity_id ?? null,
            dto.reconciled_at ?? null,
            dto.created_at,
            dto.updated_at,
        );
    }

    hasCoordinates(): boolean {
        return this.latitude !== null && this.longitude !== null;
    }

    isContacted(): boolean {
        return this.status !== PROSPECT_STATUS.NON_CONTACTE;
    }
}
