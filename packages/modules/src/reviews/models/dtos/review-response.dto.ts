import type { UserDto } from "../../../users/models/dtos/user.dto";

export type ReviewResponseDto = {
    id: string;
    review_id: string;
    responder_id: string;
    response: string;
    responder?: UserDto | null;
    created_at: string;
    updated_at: string;
};
