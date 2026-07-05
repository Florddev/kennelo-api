import type { ProspectNoteDto } from "./dtos/prospect-note.dto";

export class ProspectNoteModel {
    private constructor(
        public readonly id: string,
        public readonly prospectId: string,
        public readonly authorId: string | null,
        public readonly body: string,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ProspectNoteDto): ProspectNoteModel {
        return new ProspectNoteModel(
            dto.id,
            dto.prospect_id,
            dto.author_id ?? null,
            dto.body,
            dto.created_at,
            dto.updated_at,
        );
    }
}
