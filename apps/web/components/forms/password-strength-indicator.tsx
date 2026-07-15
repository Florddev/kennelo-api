export function getPasswordStrength(password: string): number {
    if (!password) return 0;
    let score = 0;
    if (password.length >= 12) score++;
    if (password.length >= 16) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return score;
}

export function getBarClass(index: number, strength: number): string {
    if (index >= strength) return "w-1/5 rounded-full h-1 bg-muted-foreground/20";
    if (strength === 1) return "w-1/5 rounded-full h-1 bg-destructive";
    if (strength <= 3) return "w-1/5 rounded-full h-1 bg-yellow-400";
    return "w-1/5 rounded-full h-1 bg-emerald-500";
}

export function PasswordStrengthIndicator({ value }: { value: string }) {
    const strength = getPasswordStrength(value);
    return (
        <div className="mt-2">
            <div className="flex gap-2">
                {[0, 1, 2, 3, 4].map((i) => (
                    <span key={i} className={getBarClass(i, strength)} />
                ))}
            </div>
        </div>
    );
}
