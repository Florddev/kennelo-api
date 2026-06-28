export type Phase = "idle" | "scanning" | "connecting" | "naming" | "saving";

export function derivePhase(
    saving: boolean,
    scanning: boolean,
    connecting: boolean,
    scannedCode: string | null,
    alreadyAssociated: boolean,
): Phase {
    if (saving) return "saving";
    if (scanning) return "scanning";
    if (connecting) return "connecting";
    if (scannedCode && !alreadyAssociated) return "naming";
    return "idle";
}
