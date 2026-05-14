import * as SolarIcons from "@solar-icons/react";

export type SolarIconName = keyof typeof SolarIcons;

export function DynamicIcon({
    iconName,
    className,
    DefaultIcon,
    ...props
}: SolarIcons.IconProps & {
    iconName: SolarIconName;
    className?: string;
    size?: string | number;
    color?: string;
    mirrored?: boolean;
    DefaultIcon?: React.ComponentType<SolarIcons.IconProps>;
}): React.ReactElement | null {
    const Icon = SolarIcons[iconName] as React.ComponentType<SolarIcons.IconProps> | undefined;

    if (!Icon) return DefaultIcon ? <DefaultIcon className={className} {...props} /> : null;

    return <Icon className={className} {...props} />;
}
