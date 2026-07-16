export type PhotonFeatureDto = {
    geometry: {
        type: "Point";
        coordinates: [number, number];
    };
    properties: {
        osm_id: number;
        osm_type: string;
        name?: string;
        housenumber?: string;
        street?: string;
        postcode?: string;
        city?: string;
        district?: string;
        county?: string;
        state?: string;
        country?: string;
        countrycode?: string;
        type?: string;
        extent?: [number, number, number, number];
    };
};

export type PhotonResponseDto = {
    type: "FeatureCollection";
    features: PhotonFeatureDto[];
};
