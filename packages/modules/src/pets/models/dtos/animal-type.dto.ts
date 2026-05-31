import type { AttributeDefinitionDto } from "./attribute-definition.dto";

export type AnimalTypeDto = {
    id: string;
    code: string;
    name: string;
    category: string;
    attribute_definitions?: AttributeDefinitionDto[];
};
