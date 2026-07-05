export type ProspectNoteDto = {
    id: string;
    prospect_id: string;
    author_id: string | null;
    body: string;
    created_at: string;
    updated_at: string;
};
