import Echo from "laravel-echo";
import Pusher from "pusher-js";
import type { ChannelAuthorizationCallback } from "pusher-js";

declare const process: {
    env: Record<string, string | undefined>;
};

if (typeof window !== "undefined") {
    (window as Window & { Pusher?: typeof Pusher }).Pusher = Pusher;
}

class EchoClient {
    private _echo: Echo<"reverb"> | null = null;
    private _tokenGetter: (() => Promise<string | null>) | undefined;

    setTokenGetter(fn: () => Promise<string | null>): void {
        this._tokenGetter = fn;
    }

    private async _getToken(): Promise<string | null> {
        if (this._tokenGetter) return this._tokenGetter();
        if (typeof window !== "undefined") return localStorage.getItem("access_token");
        return null;
    }

    connect(): void {
        if (typeof window === "undefined" || this._echo) return;

        const authEndpoint =
            process.env.NEXT_PUBLIC_REVERB_AUTH_ENDPOINT ??
            `${(process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api").replace("/api", "")}/broadcasting/auth`;

        this._echo = new Echo<"reverb">({
            broadcaster: "reverb",
            key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
            wsHost: process.env.NEXT_PUBLIC_REVERB_HOST ?? "localhost",
            wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080),
            wssPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080),
            forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? "http") === "https",
            enabledTransports: ["ws", "wss"],
            authorizer: (channel: { name: string }) => ({
                authorize: (socketId: string, callback: ChannelAuthorizationCallback) => {
                    this._getToken().then((token) => {
                        fetch(authEndpoint, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                            },
                            body: JSON.stringify({
                                socket_id: socketId,
                                channel_name: channel.name,
                            }),
                        })
                            .then(async (res) => {
                                const data = (await res.json().catch(() => null)) as unknown;
                                if (!res.ok) {
                                    throw new Error(
                                        `Broadcast auth failed with status ${res.status}`,
                                    );
                                }
                                if (
                                    !data ||
                                    typeof data !== "object" ||
                                    typeof (data as { auth?: unknown }).auth !== "string"
                                ) {
                                    throw new Error("Broadcast auth response is invalid");
                                }
                                callback(
                                    null,
                                    data as {
                                        auth: string;
                                        channel_data?: string;
                                        shared_secret?: string;
                                    },
                                );
                            })
                            .catch((error: unknown) =>
                                callback(
                                    error instanceof Error ? error : new Error(String(error)),
                                    null,
                                ),
                            );
                    });
                },
            }),
        });
    }

    disconnect(): void {
        this._echo?.disconnect();
        this._echo = null;
    }

    private(channel: string): ReturnType<Echo<"reverb">["private"]> {
        this.connect();
        return this._echo!.private(channel);
    }

    leave(channel: string): void {
        this._echo?.leave(channel);
    }
}

export const echoClient = new EchoClient();
