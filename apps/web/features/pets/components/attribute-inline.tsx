"use client";

import React from "react";
import { InfoSquare } from "@solar-icons/react";
import { useMessages, useTranslations } from "next-intl";
import { type AttributeDefinitionModel } from "@workspace/modules/pets";
import { DynamicIcon, type SolarIconName } from "@/components/dynamic-icon";
import { Inline, type InlineProps } from "@/components/forms/inline-input";
import { readNestedMessage, getDraftValue } from "@/features/pets/utils/attribute-form-utils";
import type { AttributeDraft } from "@/features/pets/components/forms/create-pet-stepper.types";
import type { InlineValue } from "@/components/forms/inline-inputs/types";

type Props = {
    definition: AttributeDefinitionModel;
    draft?: AttributeDraft;
    onChange: (value: InlineValue) => void;
    isLoading: boolean;
    petName: string;
};

export function AttributeInline({
    definition,
    draft,
    onChange,
    isLoading,
    petName,
}: Props): React.ReactElement {
    const t = useTranslations();
    const messages = useMessages();

    const translationKey = `features.pets.attributes_definitions.${definition.code}.question`;
    const translatedQuestion = readNestedMessage(messages, translationKey);
    const label = translatedQuestion
        ? t(translationKey, { petName })
        : t("features.pets.create.attributes.fallbackQuestion", { label: definition.label });

    const options = definition.options?.map((opt) => {
        const optKey = `features.pets.attributes_definitions.${definition.code}.options.${opt.value}`;
        return {
            value: String(opt.id),
            label: readNestedMessage(messages, optKey) ? t(optKey) : opt.label,
        };
    });

    const iconName = definition.iconName as SolarIconName | null;
    const Icon = iconName
        ? (props: {
              className?: string;
              size?: string | number;
              color?: string;
              mirrored?: boolean;
          }) => (
              <DynamicIcon
                  DefaultIcon={InfoSquare}
                  iconName={iconName}
                  className={props.className}
                  size={props.size}
                  color={props.color}
                  mirrored={props.mirrored}
              />
          )
        : InfoSquare;

    let step: number | undefined;
    if (definition.valueType === "integer") step = 1;
    else if (definition.valueType === "decimal") step = 0.1;

    return (
        <Inline
            label={label}
            Icon={Icon}
            type={definition.inputType as InlineProps["type"]}
            value={getDraftValue(definition, draft)}
            onChange={onChange}
            options={options}
            isLoading={isLoading}
            creatable={definition.code === "medications"}
            step={step}
            min={definition.inputType === "number" ? 0 : undefined}
        />
    );
}
