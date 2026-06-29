import { api } from "@workspace/common";

export async function deleteScanner(id: string): Promise<void> {
    await api.delete(`/user/scanners/${id}`);
}
