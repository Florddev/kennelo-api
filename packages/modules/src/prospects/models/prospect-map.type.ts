import type { ProspectStatusValue } from "../types/prospect-status.type";

export type ProspectFeatureProperties = {
    id: string;
    name: string;
    address: string | null;
    city: string | null;
    phone: string | null;
    website: string | null;
    google_rating: number | null;
    status: ProspectStatusValue;
    is_registered: boolean;
};

export type ProspectFeature = {
    type: "Feature";
    geometry: {
        type: "Point";
        coordinates: [number, number];
    };
    properties: ProspectFeatureProperties;
};

export type ProspectFeatureCollection = {
    type: "FeatureCollection";
    features: ProspectFeature[];
};
