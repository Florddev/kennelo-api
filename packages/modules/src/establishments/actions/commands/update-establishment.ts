import { api } from "@workspace/common";

import { EstablishmentDto } from "../../models/dtos/establishment.dto";
import { EstablishmentModel } from "../../models/establishment.model";
import { UpdateEstablishmentInput } from "../../validators/update-establishment.schema";

export async function updateEstablishment(
    id: string,
    input: UpdateEstablishmentInput,
): Promise<EstablishmentModel> {
    const body: Record<string, unknown> = {
        name: input.name,
    };

    if (input.description) body.description = input.description;
    if (input.phone) body.phone = input.phone;
    if (input.email) body.email = input.email;
    if (input.website) body.website = input.website;
    if (input.siret) body.siret = input.siret;

    if (input.address) {
        body.address = {
            line1: input.address.line1,
            line2: input.address.line2 || null,
            city: input.address.city,
            postal_code: input.address.postalCode,
            region: input.address.region || null,
            country: input.address.country,
        };
    }

    const response = await api.put<EstablishmentDto>(`/establishments/${id}`, body);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return EstablishmentModel.from(response.data);
}
