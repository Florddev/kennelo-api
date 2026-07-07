export const API_BASE = process.env.E2E_API_URL ?? "http://localhost:8000/api";

function apiUrl(path: string): string {
    const base = API_BASE.replace(/\/+$/, "");
    const suffix = path.startsWith("/") ? path : `/${path}`;
    return `${base}${suffix}`;
}

export type AuthTokens = {
    accessToken: string;
    refreshToken: string | null;
    userId: string;
    email: string;
    roles: string[];
};

export type ApiResult<T = unknown> = {
    status: number;
    data: T;
};

export type Credentials = {
    email: string;
    password: string;
};

function unwrap<T>(raw: unknown): T {
    if (raw && typeof raw === "object" && "data" in (raw as Record<string, unknown>)) {
        return (raw as { data: T }).data;
    }
    return raw as T;
}

export class ApiClient {
    private constructor(public tokens: AuthTokens | null) {}

    static async create(): Promise<ApiClient> {
        return new ApiClient(null);
    }

    static async login(credentials: Credentials): Promise<ApiClient> {
        const client = await ApiClient.create();
        await client.authenticate(credentials);
        return client;
    }

    async authenticate(credentials: Credentials): Promise<AuthTokens> {
        const response = await fetch(apiUrl("/login"), {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify(credentials),
        });
        if (!response.ok) {
            throw new Error(
                `Login failed for ${credentials.email}: ${response.status} ${await response.text()}`,
            );
        }
        const body = await response.json();
        this.tokens = this.mapTokens(body);
        return this.tokens;
    }

    async register(payload: {
        firstName: string;
        lastName: string;
        email: string;
        password: string;
    }): Promise<AuthTokens> {
        const response = await fetch(apiUrl("/register"), {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify({
                first_name: payload.firstName,
                last_name: payload.lastName,
                email: payload.email,
                password: payload.password,
                password_confirmation: payload.password,
            }),
        });
        if (!response.ok) {
            throw new Error(
                `Register failed for ${payload.email}: ${response.status} ${await response.text()}`,
            );
        }
        const body = await response.json();
        this.tokens = this.mapTokens(body);
        return this.tokens;
    }

    private mapTokens(body: Record<string, unknown>): AuthTokens {
        const user = (body.user ?? {}) as Record<string, unknown>;
        return {
            accessToken: String(body.access_token),
            refreshToken: body.refresh_token ? String(body.refresh_token) : null,
            userId: String(user.id ?? ""),
            email: String(user.email ?? ""),
            roles: Array.isArray(user.roles) ? (user.roles as string[]) : [],
        };
    }

    private authHeaders(): Record<string, string> {
        if (!this.tokens) return {};
        return { Authorization: `Bearer ${this.tokens.accessToken}` };
    }

    async get<T = unknown>(
        path: string,
        params?: Record<string, string | number | boolean>,
    ): Promise<ApiResult<T>> {
        const query = params
            ? `?${new URLSearchParams(Object.entries(params).map(([k, v]) => [k, String(v)])).toString()}`
            : "";
        return this.request<T>("GET", `${path}${query}`);
    }

    async post<T = unknown>(path: string, data?: Record<string, unknown>): Promise<ApiResult<T>> {
        return this.request<T>("POST", path, data);
    }

    async put<T = unknown>(path: string, data?: Record<string, unknown>): Promise<ApiResult<T>> {
        return this.request<T>("PUT", path, data);
    }

    async delete<T = unknown>(path: string): Promise<ApiResult<T>> {
        return this.request<T>("DELETE", path);
    }

    private async request<T>(
        method: string,
        path: string,
        data?: Record<string, unknown>,
    ): Promise<ApiResult<T>> {
        const url = apiUrl(path);
        const headers: Record<string, string> = {
            Accept: "application/json",
            ...this.authHeaders(),
        };
        if (data !== undefined) headers["Content-Type"] = "application/json";
        const response = await fetch(url, {
            method,
            headers,
            body: data !== undefined ? JSON.stringify(data) : undefined,
        });
        const status = response.status;
        if (status === 204) return { status, data: null as T };
        const text = await response.text();
        if (!text) return { status, data: null as T };
        try {
            return { status, data: unwrap<T>(JSON.parse(text)) };
        } catch {
            return { status, data: text as unknown as T };
        }
    }

    async dispose(): Promise<void> {
        return undefined;
    }
}
