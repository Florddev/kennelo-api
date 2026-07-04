export type AuthResponseDto = {
    access_token: string;
    refresh_token: string;
    token_type: string;
    expires_in: number;
    two_factor?: boolean;
    challenge_token?: string;
    remember_token?: string;
    user: {
        id: string;
        first_name: string;
        last_name: string;
        email: string;
        phone: string | null;
        locale: string;
        is_id_verified: boolean;
        email_verified_at: string | null;
        roles: string[];
    };
};

export type RefreshTokenResponseDto = {
    access_token: string;
    refresh_token: string;
    token_type: string;
    expires_in: number;
};
