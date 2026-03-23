import { api } from "@workspace/common";
import { EstablishmentModel } from "../../models/establishment.model";
import { EstablishmentDto } from "../../models/dtos/establishment.dto";

export async function getEstablishments(): Promise<EstablishmentModel[]> {
    const response = await api.get<EstablishmentDto[]>("/establishments");

    if (!response.data) {
        throw new Error("No data returned");
    }

    return response.data.map(EstablishmentModel.from);
}
