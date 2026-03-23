import { api } from "@workspace/common";
import { EstablishmentModel } from "../../models/establishment.model";
import { EstablishmentDto } from "../../models/dtos/establishment.dto";

export async function getEstablishment(id: string): Promise<EstablishmentModel> {
    const response = await api.get<EstablishmentDto>(`/establishments/${id}`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return EstablishmentModel.from(response.data);
}
