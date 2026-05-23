import type { AnimalTypeDto } from "./dtos/animal-type.dto";
import { AttributeDefinitionModel } from "./attribute-definition.model";

export class AnimalTypeModel {
    private constructor(
        public readonly id: number,
        public readonly code: string,
        public readonly name: string,
        public readonly category: string,
        public readonly attributeDefinitions: AttributeDefinitionModel[] | null,
    ) {}

    static from(dto: AnimalTypeDto): AnimalTypeModel {
        return new AnimalTypeModel(
            dto.id,
            dto.code,
            dto.name,
            dto.category,
            dto.attribute_definitions
                ? dto.attribute_definitions.map(AttributeDefinitionModel.from)
                : null,
        );
    }

    getTailwindColorClass(): string {
        return this.code ?? "main";
    }
}
