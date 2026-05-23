import { PetAttributeCategory } from "../../types/attributes-categories.type";
import type { AttributeOptionDto } from "./attribute-option.dto";

export type AttributeDefinitionDto = {
    id: number;
    code: string;
    label: string;
    value_type: string;
    input_type: string;
    icon_name: string | null;
    category: PetAttributeCategory;
    has_predefined_options: boolean;
    is_required: boolean;
    options: AttributeOptionDto[] | null;
};
