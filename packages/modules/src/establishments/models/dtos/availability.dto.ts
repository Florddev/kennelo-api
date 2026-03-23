export type AvailabilityDto = {
    id: number;
    date: string;
    status: "open" | "closed";
    note: string | null;
};
