import type { AttributeDefinitionDto } from "./dtos/attribute-definition.dto";
import { AttributeOptionModel } from "./attribute-option.model";
import { PetAttributeCategory } from "../types/attributes-categories.type";

export class AttributeDefinitionModel {
    private constructor(
        public readonly id: number,
        public readonly code: string,
        public readonly label: string,
        public readonly valueType: string,
        public readonly category: PetAttributeCategory,
        public readonly hasPredefinedOptions: boolean,
        public readonly isRequired: boolean,
        public readonly options: AttributeOptionModel[] | null,
    ) {}

    static from(dto: AttributeDefinitionDto): AttributeDefinitionModel {
        return new AttributeDefinitionModel(
            dto.id,
            dto.code,
            dto.label,
            dto.value_type,
            dto.category,
            dto.has_predefined_options,
            dto.is_required,
            dto.options ? dto.options.map(AttributeOptionModel.from) : null,
        );
    }
}
