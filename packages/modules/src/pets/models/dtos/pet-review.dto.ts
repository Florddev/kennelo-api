export type PetReviewDto = {
    id: string;
    booking_id: string;
    reviewer_id: string;
    reviewer_type: string;
    overall_rating: string;
    comment: string | null;
    would_recommend: boolean;
    is_published: boolean;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    reviewer?: {
        id: string;
        first_name: string;
        last_name: string;
        avatar_url: string | null;
    } | null;
};
