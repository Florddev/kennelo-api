import { PetReviewDto } from "./dtos/pet-review.dto";

type Reviewer = {
    id: string;
    firstName: string;
    lastName: string;
    avatarUrl: string | null;
};

export class PetReviewModel {
    private constructor(
        public readonly id: string,
        public readonly bookingId: string,
        public readonly reviewerId: string,
        public readonly reviewerType: string,
        public readonly rating: number,
        public readonly comment: string | null,
        public readonly wouldRecommend: boolean,
        public readonly isPublished: boolean,
        public readonly publishedAt: string | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
        public readonly reviewer: Reviewer | null,
    ) {}

    static from(dto: PetReviewDto): PetReviewModel {
        return new PetReviewModel(
            dto.id,
            dto.booking_id,
            dto.reviewer_id,
            dto.reviewer_type,
            parseFloat(dto.overall_rating),
            dto.comment,
            dto.would_recommend,
            dto.is_published,
            dto.published_at,
            dto.created_at,
            dto.updated_at,
            dto.reviewer
                ? {
                      id: dto.reviewer.id,
                      firstName: dto.reviewer.first_name,
                      lastName: dto.reviewer.last_name,
                      avatarUrl: dto.reviewer.avatar_url,
                  }
                : null,
        );
    }

    getReviewerName(): string {
        if (!this.reviewer) return "";
        return `${this.reviewer.firstName} ${this.reviewer.lastName}`.trim();
    }

    getReviewerInitials(): string {
        if (!this.reviewer) return "?";
        const first = this.reviewer.firstName?.[0] ?? "";
        const last = this.reviewer.lastName?.[0] ?? "";
        return `${first}${last}`.toUpperCase() || "?";
    }
}
