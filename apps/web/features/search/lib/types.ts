import type { DateRange } from "react-day-picker";
import type { PlaceSuggestionModel } from "@workspace/modules/address";

export type PetType = "dog" | "cat" | "bird" | "reptile";
export type PetCounts = Record<PetType, number>;
export type ActivePanel = "location" | "dates" | "pets" | null;

export type LocationSuggestion = PlaceSuggestionModel;

export type SelectedPlace = {
    label: string;
    latitude: number;
    longitude: number;
    radiusKm: number;
};

export type RecentSearch = {
    id: string;
    location: string;
    dateFrom: Date;
    dateTo: Date;
    petCount: number;
};

export type SearchBarState = {
    activePanel: ActivePanel;
    location: string;
    dateRange: DateRange | undefined;
    petCounts: PetCounts;
};
