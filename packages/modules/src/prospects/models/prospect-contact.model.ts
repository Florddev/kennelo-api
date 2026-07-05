import type { ProspectContactDto } from "./dtos/prospect-contact.dto";
import type { ProspectContactTypeValue } from "../types/prospect-contact-type.type";

export class ProspectContactModel {
    private constructor(
        public readonly id: string,
        public readonly prospectId: string,
        public readonly authorId: string | null,
        public readonly type: ProspectContactTypeValue,
        public readonly contactedAt: string,
        public readonly outcome: string | null,
        public readonly notes: string | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: ProspectContactDto): ProspectContactModel {
        return new ProspectContactModel(
            dto.id,
            dto.prospect_id,
            dto.author_id ?? null,
            dto.type,
            dto.contacted_at,
            dto.outcome ?? null,
            dto.notes ?? null,
            dto.created_at,
        );
    }
}
