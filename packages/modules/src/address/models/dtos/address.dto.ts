export type AddressDto = {
    id: string;
    line1: string;
    line2: string | null;
    postal_code: string;
    city: string;
    region: string | null;
    country: string;
    latitude: number | null;
    longitude: number | null;
    created_at: string;
    updated_at: string;
};
