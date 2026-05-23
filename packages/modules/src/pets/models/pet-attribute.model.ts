import type { PetAttributeDto } from "./dtos/pet-attribute.dto";
import { AttributeDefinitionModel } from "./attribute-definition.model";
import { AttributeOptionModel } from "./attribute-option.model";

export class PetAttributeModel {
    private constructor(
        public readonly id: number,
        public readonly attributeDefinitionId: number,
        public readonly attributeOptionId: string | null,
        public readonly value: string | number | boolean | null,
        public readonly attributeDefinition: AttributeDefinitionModel | null,
        public readonly attributeOption: AttributeOptionModel | null,
    ) {}

    static from(dto: PetAttributeDto): PetAttributeModel {
        return new PetAttributeModel(
            dto.id,
            dto.attribute_definition_id,
            dto.attribute_option_id,
            dto.value,
            dto.attribute_definition
                ? AttributeDefinitionModel.from(dto.attribute_definition)
                : null,
            dto.attribute_option ? AttributeOptionModel.from(dto.attribute_option) : null,
        );
    }

    displayValue(yesText: string, noText: string): string {
        if (this.attributeOption?.label) return this.attributeOption.label;
        if (typeof this.value === "boolean") return this.value ? yesText : noText;
        return String(this.value ?? "—");
    }
}
