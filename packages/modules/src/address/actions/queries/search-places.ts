import { PlaceSuggestionModel } from "../../models/place-suggestion.model";
import type { PhotonResponseDto } from "../../models/dtos/photon-feature.dto";

const PHOTON_ENDPOINT = "https://photon.komoot.io/api";
const SUPPORTED_LANGUAGES = ["en", "de", "fr", "it"] as const;
const PLACE_TAGS = ["place:city", "place:town", "place:village", "place:state"];
const DEFAULT_LIMIT = 6;
const MIN_QUERY_LENGTH = 2;

type SearchPlacesOptions = {
    limit?: number;
    lang?: string;
    signal?: AbortSignal;
};

function resolveLanguage(lang?: string): string {
    return SUPPORTED_LANGUAGES.includes(lang as (typeof SUPPORTED_LANGUAGES)[number])
        ? (lang as string)
        : "en";
}

export async function searchPlaces(
    query: string,
    options: SearchPlacesOptions = {},
): Promise<PlaceSuggestionModel[]> {
    const trimmed = query.trim();

    if (trimmed.length < MIN_QUERY_LENGTH) {
        return [];
    }

    const params = new URLSearchParams({
        q: trimmed,
        limit: String(options.limit ?? DEFAULT_LIMIT),
        lang: resolveLanguage(options.lang),
    });

    for (const tag of PLACE_TAGS) {
        params.append("osm_tag", tag);
    }

    const response = await fetch(`${PHOTON_ENDPOINT}?${params.toString()}`, {
        signal: options.signal,
    });

    if (!response.ok) {
        throw new Error("Place search failed");
    }

    const data = (await response.json()) as PhotonResponseDto;

    return (data.features ?? [])
        .filter((feature) => feature.geometry?.coordinates?.length === 2)
        .map(PlaceSuggestionModel.from)
        .filter((place) => place.name !== "");
}
