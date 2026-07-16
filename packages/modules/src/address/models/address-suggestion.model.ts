import type { PhotonFeatureDto } from "./dtos/photon-feature.dto";

export class AddressSuggestionModel {
    private constructor(
        public readonly id: string,
        public readonly label: string,
        public readonly line1: string,
        public readonly city: string,
        public readonly postalCode: string,
        public readonly region: string,
        public readonly country: string,
        public readonly latitude: number,
        public readonly longitude: number,
    ) {}

    static from(dto: PhotonFeatureDto): AddressSuggestionModel {
        const properties = dto.properties;
        const street = [properties.housenumber, properties.street].filter(Boolean).join(" ");
        const line1 = street || properties.name || "";
        const city = properties.city ?? properties.district ?? properties.county ?? "";
        const label = [line1, properties.postcode, city, properties.country]
            .filter(Boolean)
            .join(", ");

        return new AddressSuggestionModel(
            `${properties.osm_type}-${properties.osm_id}-${dto.geometry.coordinates.join(",")}`,
            label,
            line1,
            city,
            properties.postcode ?? "",
            properties.state ?? "",
            (properties.countrycode ?? "").toUpperCase(),
            dto.geometry.coordinates[1],
            dto.geometry.coordinates[0],
        );
    }
}
