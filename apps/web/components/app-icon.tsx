import { cn } from "@workspace/ui/lib/utils";
import { type IconProps } from "@solar-icons/react";

export default function AppIcon({
    Icon,
    active,
    className,
    ...props
}: IconProps & {
    Icon: React.ComponentType<IconProps>;
    active?: boolean;
    className?: string;
}) {
    return (
        <Icon
            weight={active ? "BoldDuotone" : "Linear"}
            className={cn(
                "transition-colors size-auto",
                active &&
                    "scale-110 text-primary [&_*[opacity]]:opacity-100 [&_*[opacity]]:text-secondary",
                className,
            )}
            {...props}
        />
    );
}
