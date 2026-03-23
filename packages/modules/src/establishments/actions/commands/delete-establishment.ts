import { api } from "@workspace/common";

export async function deleteEstablishment(id: string): Promise<void> {
    await api.delete(`/establishments/${id}`);
}
