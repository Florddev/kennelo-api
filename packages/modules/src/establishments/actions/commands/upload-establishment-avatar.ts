import { api } from "@workspace/common";
import type { EstablishmentDto } from "../../models/dtos/establishment.dto";
import { EstablishmentModel } from "../../models/establishment.model";

export async function uploadEstablishmentAvatar(
    establishmentId: string,
    avatar: File,
): Promise<EstablishmentModel> {
    const formData = new FormData();
    formData.append("avatar", avatar);
    const response = await api.post<EstablishmentDto>(
        `/establishments/${establishmentId}/avatar`,
        formData,
    );
    if (!response.data) throw new Error("Failed to upload avatar");
    return EstablishmentModel.from(response.data);
}
