export type HostType = "pro" | "particulier";

const SERVICE_GARDE_DOMICILE = "${SERVICE_GARDE_DOMICILE}";

export type PetBadge = { code: string; label: string };

export type MockHost = {
    id: string;
    name: string;
    type: HostType;
    serviceType: string;
    distanceKm: number;
    rating: number;
    reviewCount: number;
    pricePerNight: number;
    petBadges: PetBadge[];
};

export const MOCK_HOSTS: MockHost[] = [
    {
        id: "1",
        name: "Pension du Menez",
        type: "pro",
        serviceType: "Pension",
        distanceKm: 3.2,
        rating: 4.8,
        reviewCount: 47,
        pricePerNight: 28,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
            { code: "R", label: "Rongeur" },
        ],
    },
    {
        id: "2",
        name: "Marie L.",
        type: "particulier",
        serviceType: SERVICE_GARDE_DOMICILE,
        distanceKm: 1.1,
        rating: 4.9,
        reviewCount: 23,
        pricePerNight: 22,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
        ],
    },
    {
        id: "3",
        name: "Animaland Carhaix",
        type: "pro",
        serviceType: "Pension",
        distanceKm: 4.4,
        rating: 4.7,
        reviewCount: 112,
        pricePerNight: 32,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
            { code: "O", label: "Oiseau" },
            { code: "R", label: "Rongeur" },
        ],
    },
    {
        id: "4",
        name: "Sophie D.",
        type: "particulier",
        serviceType: SERVICE_GARDE_DOMICILE,
        distanceKm: 2.1,
        rating: 4.9,
        reviewCount: 31,
        pricePerNight: 18,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
        ],
    },
    {
        id: "5",
        name: "La Ferme des Bêtes",
        type: "pro",
        serviceType: "Pension",
        distanceKm: 6.8,
        rating: 4.6,
        reviewCount: 58,
        pricePerNight: 35,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
            { code: "E", label: "Équidé" },
            { code: "R", label: "Rongeur" },
        ],
    },
    {
        id: "6",
        name: "Thomas B.",
        type: "particulier",
        serviceType: "Promenade",
        distanceKm: 0.8,
        rating: 5.0,
        reviewCount: 12,
        pricePerNight: 15,
        petBadges: [{ code: "C", label: "Chien" }],
    },
    {
        id: "7",
        name: "Les Quatre Pattes",
        type: "pro",
        serviceType: "Pension",
        distanceKm: 7.2,
        rating: 4.7,
        reviewCount: 89,
        pricePerNight: 30,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
            { code: "Re", label: "Reptile" },
        ],
    },
    {
        id: "8",
        name: "Clara M.",
        type: "particulier",
        serviceType: SERVICE_GARDE_DOMICILE,
        distanceKm: 3.5,
        rating: 4.8,
        reviewCount: 19,
        pricePerNight: 20,
        petBadges: [
            { code: "C", label: "Chien" },
            { code: "Ch", label: "Chat" },
            { code: "O", label: "Oiseau" },
        ],
    },
];

export const MOCK_NEARBY = MOCK_HOSTS.slice(0, 4);
export const MOCK_WEEKEND = MOCK_HOSTS.filter((_, i) => [3, 1, 5, 2].includes(i));
export const MOCK_PROS = MOCK_HOSTS.filter((h) => h.type === "pro");
export const MOCK_PARTICULIERS = MOCK_HOSTS.filter((h) => h.type === "particulier");
export const MOCK_NEW = MOCK_HOSTS.filter((_, i) => [5, 7, 4].includes(i));
