import type { ProfessionalDto } from "./dtos/professional.dto";
import { ACTIVITY_STATUS, type ActivityStatusValue } from "../types/activity-status.type";

export type ProfessionalManager = {
    id: string;
    firstName: string;
    lastName: string;
    email: string;
};

export class ProfessionalModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly type: string | null,
        public readonly status: ActivityStatusValue,
        public readonly isActive: boolean,
        public readonly isProfessional: boolean,
        public readonly siret: string | null,
        public readonly siren: string | null,
        public readonly apeCode: string | null,
        public readonly phone: string | null,
        public readonly email: string | null,
        public readonly website: string | null,
        public readonly companyVerifiedAt: string | null,
        public readonly rejectionReason: string | null,
        public readonly reviewedAt: string | null,
        public readonly manager: ProfessionalManager | null,
        public readonly city: string | null,
        public readonly department: string | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: ProfessionalDto): ProfessionalModel {
        return new ProfessionalModel(
            dto.id,
            dto.name,
            dto.type ?? null,
            dto.status,
            dto.is_active ?? false,
            dto.is_professional ?? false,
            dto.siret ?? null,
            dto.siren ?? null,
            dto.ape_code ?? null,
            dto.phone ?? null,
            dto.email ?? null,
            dto.website ?? null,
            dto.company_verified_at || null,
            dto.rejection_reason ?? null,
            dto.reviewed_at || null,
            dto.manager
                ? {
                      id: dto.manager.id,
                      firstName: dto.manager.first_name,
                      lastName: dto.manager.last_name,
                      email: dto.manager.email,
                  }
                : null,
            dto.address?.city ?? null,
            dto.address?.department ?? null,
            dto.created_at,
        );
    }

    isPending(): boolean {
        return this.status === ACTIVITY_STATUS.PENDING;
    }

    isVerified(): boolean {
        return this.companyVerifiedAt !== null && this.companyVerifiedAt !== "";
    }
}
