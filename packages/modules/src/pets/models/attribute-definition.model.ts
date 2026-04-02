import type { AttributeDefinitionDto } from "./dtos/attribute-definition.dto";
import { AttributeOptionModel } from "./attribute-option.model";
import { PetAttributeCategory } from "../types/attributes-categories.type";

type AttributeValuePayload =
    | { valueText: string }
    | { valueInteger: number }
    | { valueDecimal: number }
    | { valueBoolean: boolean }
    | { valueDate: string };

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

    toValuePayload(rawValue: string): AttributeValuePayload | null {
        const normalizedText = rawValue.trim();

        if (!normalizedText) {
            return null;
        }

        if (this.valueType === "integer") {
            const parsedInteger = Number.parseInt(normalizedText, 10);
            if (Number.isNaN(parsedInteger)) {
                return null;
            }

            return { valueInteger: parsedInteger };
        }

        if (this.valueType === "decimal") {
            const parsedDecimal = Number.parseFloat(normalizedText);
            if (Number.isNaN(parsedDecimal)) {
                return null;
            }

            return { valueDecimal: parsedDecimal };
        }

        if (this.valueType === "date") {
            return { valueDate: normalizedText };
        }

        if (this.valueType === "boolean") {
            if (normalizedText.toLowerCase() === "true") {
                return { valueBoolean: true };
            }

            if (normalizedText.toLowerCase() === "false") {
                return { valueBoolean: false };
            }

            return null;
        }

        return { valueText: normalizedText };
    }
}
