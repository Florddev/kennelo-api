import type { ProspectContactTypeValue } from "../../types/prospect-contact-type.type";

export type ProspectContactDto = {
    id: string;
    prospect_id: string;
    author_id: string | null;
    type: ProspectContactTypeValue;
    contacted_at: string;
    outcome: string | null;
    notes: string | null;
    created_at: string;
};
