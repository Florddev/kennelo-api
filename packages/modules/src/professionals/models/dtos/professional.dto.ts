import type { ActivityStatusValue } from "../../types/activity-status.type";

export type ProfessionalAddressDto = {
    id: string;
    line1: string | null;
    city: string | null;
    postal_code: string | null;
    department: string | null;
    region: string | null;
    country: string | null;
};

export type ProfessionalManagerDto = {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
};

export type ProfessionalDto = {
    id: string;
    name: string;
    type: string | null;
    status: ActivityStatusValue;
    is_active: boolean;
    is_professional: boolean;
    siret: string | null;
    siren: string | null;
    ape_code: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    google_place_id: string | null;
    google_rating: number | null;
    google_reviews_count: number | null;
    google_maps_url: string | null;
    is_google_linked: boolean;
    google_synced_at: string | null;
    company_verified_at: string | null;
    company_verification_data: Record<string, unknown> | null;
    rejection_reason: string | null;
    reviewed_at: string | null;
    manager: ProfessionalManagerDto | null;
    address: ProfessionalAddressDto | null;
    created_at: string;
    updated_at: string;
};
