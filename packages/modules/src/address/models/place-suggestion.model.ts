import type { PhotonFeatureDto } from "./dtos/photon-feature.dto";

const EARTH_RADIUS_KM = 6371;
const DEFAULT_RADIUS_KM = 25;
const MIN_RADIUS_KM = 5;
const MAX_RADIUS_KM = 100;

function haversineKm(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const toRad = (degrees: number) => (degrees * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;

    return EARTH_RADIUS_KM * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function radiusFromExtent(
    latitude: number,
    longitude: number,
    extent?: [number, number, number, number],
): number {
    if (!extent) {
        return DEFAULT_RADIUS_KM;
    }

    const [west, north, east, south] = extent;
    const corner = Math.max(
        haversineKm(latitude, longitude, north, west),
        haversineKm(latitude, longitude, south, east),
    );

    return Math.min(Math.max(Math.ceil(corner), MIN_RADIUS_KM), MAX_RADIUS_KM);
}

export class PlaceSuggestionModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly region: string,
        public readonly country: string,
        public readonly latitude: number,
        public readonly longitude: number,
        public readonly radiusKm: number,
    ) {}

    static from(dto: PhotonFeatureDto): PlaceSuggestionModel {
        const properties = dto.properties;
        const latitude = dto.geometry.coordinates[1];
        const longitude = dto.geometry.coordinates[0];

        return new PlaceSuggestionModel(
            `${properties.osm_type}-${properties.osm_id}`,
            properties.name ?? properties.city ?? "",
            properties.state ?? properties.county ?? "",
            properties.country ?? "",
            latitude,
            longitude,
            radiusFromExtent(latitude, longitude, properties.extent),
        );
    }

    getSubtitle(): string {
        return [this.region, this.country].filter(Boolean).join(", ");
    }

    getLabel(): string {
        return [this.name, this.region, this.country].filter(Boolean).join(", ");
    }
}
