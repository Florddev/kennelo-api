export type RequestConfig = {
    url: string;
    method: string;
    options: RequestInit;
};

export type ApiResponse<T> = {
    status: number;
    data: T | null;
};

export type ApiRequestOptions = RequestInit & {
    skipAuth?: boolean;
};

interface ApiError extends Error {
    status: number;
    data: unknown;
    response: Response;
    fieldErrors?: Record<string, string[]>;
}

class ApiClient {
    public readonly baseUrl = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";
    public defaultTimeoutMs = 30000;

    private _tokenGetter: (() => Promise<string | null>) | undefined;

    setTokenGetter(fn: () => Promise<string | null>): void {
        this._tokenGetter = fn;
    }

    private _buildUrl(path: string, params?: Record<string, string | number | boolean>): string {
        const url = new URL(this.baseUrl + path);

        if (params) {
            Object.entries(params).forEach(([k, v]) => url.searchParams.append(k, String(v)));
        }

        return url.toString();
    }

    private async _handleResponse<T>(resp: Response): Promise<ApiResponse<T>> {
        const status = resp.status;
        let data: T | null = null;
        const text = await resp.text();

        if (text) {
            try {
                const parsed = JSON.parse(text);
                data = (
                    parsed && typeof parsed === "object" && "data" in parsed ? parsed.data : parsed
                ) as T;
            } catch {
                data = null;
            }
        }

        if (status >= 400) {
            const apiData = data as { message?: string; errors?: Record<string, string[]> } | null;
            let errorMessage = `HTTP Error ${status}`;

            if (apiData?.message) {
                errorMessage = apiData.message;
            } else if (apiData?.errors) {
                const firstField = Object.values(apiData.errors)[0];
                if (firstField?.[0]) errorMessage = firstField[0];
            }

            const error = new Error(errorMessage) as ApiError;
            error.status = status;
            error.data = data;
            error.response = resp;
            error.fieldErrors = apiData?.errors;
            throw error;
        }

        return { status, data };
    }

    private async _fetchApi(
        url: string,
        method: string,
        options: ApiRequestOptions = {},
    ): Promise<Response> {
        const { skipAuth, ...requestInit } = options;
        const config: RequestConfig = { url, method, options: requestInit };

        const token = skipAuth
            ? null
            : this._tokenGetter
              ? await this._tokenGetter()
              : typeof window !== "undefined"
                ? localStorage.getItem("access_token")
                : null;

        const isFormData = config.options.body instanceof FormData;
        const headers: Record<string, string> = {
            ...(!isFormData && { "Content-Type": "application/json" }),
            ...(config.options.headers as Record<string, string>),
        };

        if (token) {
            headers["Authorization"] = `Bearer ${token}`;
        }

        const hasCallerSignal = config.options.signal != null;
        const useTimeout = !hasCallerSignal && !isFormData && this.defaultTimeoutMs > 0;
        const controller = useTimeout ? new AbortController() : null;
        const timeoutId: ReturnType<typeof setTimeout> | null = controller
            ? setTimeout(() => controller.abort(), this.defaultTimeoutMs)
            : null;

        try {
            return await fetch(config.url, {
                ...config.options,
                method: config.method ?? "GET",
                headers,
                credentials: process.env.NEXT_PUBLIC_PLATFORM === "mobile" ? "omit" : "include",
                ...(controller ? { signal: controller.signal } : {}),
            });
        } finally {
            if (timeoutId) clearTimeout(timeoutId);
        }
    }

    private async _request<T>(
        path: string,
        method: string,
        options: ApiRequestOptions = {},
        params?: Record<string, string | number | boolean>,
    ): Promise<ApiResponse<T>> {
        const url = this._buildUrl(path, params);
        const response = await this._fetchApi(url, method, options);
        return await this._handleResponse<T>(response);
    }

    async get<T = unknown>(
        path: string,
        params?: Record<string, string | number | boolean>,
        options?: ApiRequestOptions,
    ): Promise<ApiResponse<T>> {
        return this._request<T>(path, "GET", { ...options }, params);
    }

    async post<T = unknown>(
        path: string,
        body?: unknown,
        options?: ApiRequestOptions,
    ): Promise<ApiResponse<T>> {
        const serialized = body instanceof FormData ? body : JSON.stringify(body);
        return this._request<T>(path, "POST", { body: serialized, ...options });
    }

    async put<T = unknown>(
        path: string,
        body?: unknown,
        options?: ApiRequestOptions,
    ): Promise<ApiResponse<T>> {
        return this._request<T>(path, "PUT", { body: JSON.stringify(body), ...options });
    }

    async delete<T = unknown>(path: string, options?: ApiRequestOptions): Promise<ApiResponse<T>> {
        return this._request<T>(path, "DELETE", { ...options });
    }
}

export const api = new ApiClient();
