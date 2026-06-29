export function getAge(birthDate: string): { years: number; months: number } {
    const birth = new Date(birthDate);
    const now = new Date();
    let years = now.getFullYear() - birth.getFullYear();
    let months = now.getMonth() - birth.getMonth();
    if (months < 0) {
        years--;
        months += 12;
    }
    return { years, months };
}

export function formatAgeDisplay(
    birthDate: string | null | undefined,
    formatYears: (count: number) => string,
    formatMonths: (count: number) => string,
): string | null {
    if (!birthDate) return null;
    const { years, months } = getAge(birthDate);
    return years >= 1 ? formatYears(years) : formatMonths(months);
}
