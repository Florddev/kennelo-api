import type { AvailabilityDto } from "./dtos/availability.dto";

export class AvailabilityModel {
    private constructor(
        public readonly id: number,
        public readonly date: string,
        public readonly status: "open" | "closed",
        public readonly note: string | null,
    ) {}

    static from(dto: AvailabilityDto): AvailabilityModel {
        return new AvailabilityModel(dto.id, dto.date, dto.status, dto.note);
    }
}
