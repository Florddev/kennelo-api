import type { ActivityImageDto } from "./dtos/activity-image.dto";

export class ActivityImageModel {
    private constructor(
        public readonly id: string,
        public readonly url: string,
        public readonly order: number,
        public readonly createdAt: string,
    ) {}

    static from(dto: ActivityImageDto): ActivityImageModel {
        return new ActivityImageModel(dto.id, dto.url, dto.order, dto.created_at);
    }
}
