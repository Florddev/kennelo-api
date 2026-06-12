import { api } from "@workspace/common";

export async function broadcastPet(microchipNumber: string, scannerCode: string): Promise<void> {
    await api.post<void>("/pets/broadcast", {
        microchip_number: microchipNumber,
        scanner_code: scannerCode,
    });
}
