import type { PetType, RecentSearch } from "./types";

export const PET_TYPES: PetType[] = ["dog", "cat", "bird", "reptile"];

export const RECENT_SEARCHES: RecentSearch[] = [
    {
        id: "recent-1",
        location: "Paris, Île-de-France",
        dateFrom: new Date(2026, 3, 10),
        dateTo: new Date(2026, 3, 15),
        petCount: 2,
    },
    {
        id: "recent-2",
        location: "Lyon, Auvergne-Rhône-Alpes",
        dateFrom: new Date(2026, 4, 1),
        dateTo: new Date(2026, 4, 7),
        petCount: 1,
    },
];
