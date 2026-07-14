import { randomUUID } from "node:crypto";

export const E2E_PREFIX = "E2E";
export const E2E_EMAIL_DOMAIN = "kennelo-e2e.test";
export const E2E_STRONG_PASSWORD = "E2ePass123!";

export function e2eLabel(kind: string): string {
    return `${E2E_PREFIX}-${kind}-${randomUUID().slice(0, 8)}`;
}

export function e2eEmail(): string {
    return `e2e+${randomUUID()}@${E2E_EMAIL_DOMAIN}`;
}

export function isE2eEmail(email: string | null | undefined): boolean {
    return typeof email === "string" && email.endsWith(`@${E2E_EMAIL_DOMAIN}`);
}

export function isE2eLabel(value: string | null | undefined): boolean {
    return typeof value === "string" && value.startsWith(`${E2E_PREFIX}-`);
}

function isoDate(offsetDays: number): string {
    const base = new Date();
    base.setUTCHours(0, 0, 0, 0);
    base.setUTCDate(base.getUTCDate() + offsetDays);
    return base.toISOString().slice(0, 10);
}

export const dateRange = {
    from: (offsetDays = 3) => isoDate(offsetDays),
    to: (offsetDays = 6) => isoDate(offsetDays),
    today: () => isoDate(0),
};

export function newUserPayload(
    overrides: Partial<{
        firstName: string;
        lastName: string;
        email: string;
        password: string;
    }> = {},
) {
    return {
        firstName: overrides.firstName ?? "E2eTest",
        lastName: overrides.lastName ?? "User",
        email: overrides.email ?? e2eEmail(),
        password: overrides.password ?? E2E_STRONG_PASSWORD,
    };
}

export function newPetPayload(animalTypeId: string, overrides: Record<string, unknown> = {}) {
    return {
        animal_type_id: animalTypeId,
        name: e2eLabel("Pet"),
        breed: `${E2E_PREFIX} Breed`,
        sex: "male",
        weight: 5.5,
        is_sterilized: true,
        has_microchip: false,
        about: `${E2E_PREFIX} created pet`,
        ...overrides,
    };
}

export function newActivityPayload(overrides: Record<string, unknown> = {}) {
    return {
        name: e2eLabel("Activity"),
        description: `${E2E_PREFIX} boarding activity`,
        address: {
            line1: "1 Rue de Test",
            city: "Paris",
            postal_code: "75001",
            country: "FR",
        },
        ...overrides,
    };
}

export function newAvailabilityPayload(overrides: Record<string, unknown> = {}) {
    return {
        start_date: dateRange.today(),
        end_date: dateRange.to(300),
        status: "open",
        ...overrides,
    };
}

export function newCyclePayload(overrides: Record<string, unknown> = {}) {
    return {
        start_date: dateRange.from(1),
        end_date: dateRange.to(30),
        priority: 1,
        is_active: true,
        color: "#3366ff",
        ...overrides,
    };
}
