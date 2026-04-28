import { jwtHelper, LocalStorageService } from "@workspace/common";
import type { IStorageService } from "@workspace/common";
import { UserRole } from "../types/user-roles.type";

const ACCESS_TOKEN_KEY = "access_token";
const REFRESH_TOKEN_KEY = "refresh_token";

let _storage: IStorageService = new LocalStorageService();

export const authService = {
    configure(storage: IStorageService): void {
        _storage = storage;
    },

    async getAccessToken(): Promise<string | null> {
        return _storage.get(ACCESS_TOKEN_KEY);
    },

    async getRefreshToken(): Promise<string | null> {
        return _storage.get(REFRESH_TOKEN_KEY);
    },

    async isAuthenticated(): Promise<boolean> {
        return (await _storage.get(ACCESS_TOKEN_KEY)) !== null;
    },

    async clearTokens(): Promise<void> {
        await Promise.all([_storage.remove(ACCESS_TOKEN_KEY), _storage.remove(REFRESH_TOKEN_KEY)]);
    },

    async setTokens(accessToken: string, refreshToken: string): Promise<void> {
        await Promise.all([
            _storage.set(ACCESS_TOKEN_KEY, accessToken),
            _storage.set(REFRESH_TOKEN_KEY, refreshToken),
        ]);
    },

    async isAccessTokenExpired(): Promise<boolean> {
        const token = await this.getAccessToken();
        if (!token) return true;
        return jwtHelper.isExpired(token);
    },

    async getUserRoles(): Promise<UserRole[]> {
        const token = await this.getAccessToken();
        if (!token) return [];
        return jwtHelper.getProperty<UserRole[]>(token, "roles") || [];
    },
} as const;
