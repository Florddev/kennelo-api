import { test as base, expect } from "@playwright/test";
import { ApiClient, type AuthTokens } from "./api-client";
import { newActivityPayload, newPetPayload, newUserPayload } from "./data";

export const SEEDED_USER = { email: "user@orus.com", password: "user" };
export const SEEDED_MANAGER = { email: "manager@orus.com", password: "manager" };

type TrackedResource = {
    label: string;
    remove: () => Promise<void>;
};

export type EphemeralUser = {
    tokens: AuthTokens;
    email: string;
    password: string;
    api: ApiClient;
};

type Fixtures = {
    api: ApiClient;
    managerApi: ApiClient;
    track: (resource: TrackedResource) => void;
    createPet: (overrides?: Record<string, unknown>) => Promise<{ id: string; name: string }>;
    ephemeralUser: () => Promise<EphemeralUser>;
    animalTypeId: string;
    seededActivity: { id: string; name: string };
    ephemeralActivity: () => Promise<{ id: string; name: string }>;
};

export const test = base.extend<Fixtures>({
    api: async ({}, use) => {
        const client = await ApiClient.login(SEEDED_USER);
        await use(client);
        await client.dispose();
    },

    managerApi: async ({}, use) => {
        const client = await ApiClient.login(SEEDED_MANAGER);
        await use(client);
        await client.dispose();
    },

    animalTypeId: async ({ api }, use) => {
        const { data } = await api.get<Array<{ id: string }>>("/animal-types");
        const first = Array.isArray(data) ? data[0] : undefined;
        expect(first, "no seeded animal types").toBeTruthy();
        await use(String(first!.id));
    },

    seededActivity: async ({ managerApi }, use) => {
        const { data } = await managerApi.get<
            Array<{ id: string; name: string; is_active?: boolean }>
        >("/activities", { per_page: 20 });
        const list = Array.isArray(data) ? data : [];
        const active = list.find((a) => a.is_active !== false) ?? list[0];
        expect(active, "no seeded activities for manager").toBeTruthy();
        await use({ id: String(active!.id), name: String(active!.name) });
    },

    track: async ({}, use) => {
        const tracked: TrackedResource[] = [];
        await use((resource) => tracked.push(resource));
        for (const resource of tracked.reverse()) {
            try {
                await resource.remove();
            } catch (error) {
                console.warn(`[e2e] cleanup failed for ${resource.label}: ${String(error)}`);
            }
        }
    },

    createPet: async ({ api, animalTypeId, track }, use) => {
        await use(async (overrides = {}) => {
            const { data } = await api.post<{ id: string; name: string }>(
                "/pets",
                newPetPayload(animalTypeId, overrides),
            );
            track({
                label: `pet:${data.id}`,
                remove: () => api.delete(`/pets/${data.id}`).then(() => undefined),
            });
            return { id: data.id, name: data.name };
        });
    },

    ephemeralActivity: async ({ managerApi, track }, use) => {
        await use(async () => {
            const { data } = await managerApi.post<{ id: string; name: string }>(
                "/activities",
                newActivityPayload(),
            );
            track({
                label: `activity:${data.id}`,
                remove: () => managerApi.delete(`/activities/${data.id}`).then(() => undefined),
            });
            return { id: data.id, name: data.name };
        });
    },

    ephemeralUser: async ({}, use) => {
        const created: ApiClient[] = [];
        await use(async () => {
            const payload = newUserPayload();
            const client = await ApiClient.create();
            const tokens = await client.register(payload);
            created.push(client);
            return { tokens, email: payload.email, password: payload.password, api: client };
        });
        for (const client of created) {
            try {
                await client.delete("/user");
            } catch (error) {
                console.warn(`[e2e] ephemeral user cleanup failed: ${String(error)}`);
            }
            await client.dispose();
        }
    },
});

export { expect };
