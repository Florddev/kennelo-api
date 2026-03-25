import { AuthResponseDto } from "./dtos/auth.dto";
import { UserModel } from "./user.model";

export class AuthModel {
    private constructor(
        public readonly accessToken: string,
        public readonly refreshToken: string,
        public readonly tokenType: string,
        public readonly expiresIn: number,
        public readonly user: UserModel,
    ) {}

    static from(dto: AuthResponseDto): AuthModel {
        return new AuthModel(
            dto.access_token,
            dto.refresh_token,
            dto.token_type,
            dto.expires_in,
            UserModel.from({
                ...dto.user,
                avatar_url: null,
                address: null,
                status: "active",
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
            }),
        );
    }

    getBearerToken(): string {
        return `${this.tokenType} ${this.accessToken}`;
    }
}
