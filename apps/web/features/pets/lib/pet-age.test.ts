import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { getAge } from "./pet-age";

describe("getAge", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date("2026-07-16"));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("donne 2 ans pour une naissance il y a exactement 2 ans", () => {
        expect(getAge("2024-07-16")).toEqual({ years: 2, months: 0 });
    });

    it("donne 3 mois pour une naissance il y a 3 mois", () => {
        expect(getAge("2026-04-16")).toEqual({ years: 0, months: 3 });
    });
});
