import { AddressSuggestionModel } from "../../models/address-suggestion.model";
import type { PhotonResponseDto } from "../../models/dtos/photon-feature.dto";

const PHOTON_ENDPOINT = "https://photon.komoot.io/api";
const SUPPORTED_LANGUAGES = ["en", "de", "fr", "it"] as const;
const DEFAULT_LIMIT = 5;

type SearchAddressesOptions = {
    limit?: number;
    lang?: string;
    signal?: AbortSignal;
};

function resolveLanguage(lang?: string): string {
    return SUPPORTED_LANGUAGES.includes(lang as (typeof SUPPORTED_LANGUAGES)[number])
        ? (lang as string)
        : "en";
}

export async function searchAddresses(
    query: string,
    options: SearchAddressesOptions = {},
): Promise<AddressSuggestionModel[]> {
    const trimmed = query.trim();

    if (trimmed.length < 3) {
        return [];
    }

    const params = new URLSearchParams({
        q: trimmed,
        limit: String(options.limit ?? DEFAULT_LIMIT),
        lang: resolveLanguage(options.lang),
    });

    const response = await fetch(`${PHOTON_ENDPOINT}?${params.toString()}`, {
        signal: options.signal,
    });

    if (!response.ok) {
        throw new Error("Address search failed");
    }

    const data = (await response.json()) as PhotonResponseDto;

    return (data.features ?? [])
        .filter((feature) => feature.geometry?.coordinates?.length === 2)
        .map(AddressSuggestionModel.from);
}
