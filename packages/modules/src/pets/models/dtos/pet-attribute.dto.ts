import type { AttributeDefinitionDto } from "./attribute-definition.dto";
import type { AttributeOptionDto } from "./attribute-option.dto";

export type PetAttributeDto = {
    id: number;
    attribute_definition_id: number;
    attribute_option_id: string | null;
    value: string | number | boolean | null;
    attribute_definition?: AttributeDefinitionDto;
    attribute_option?: AttributeOptionDto | null;
};
