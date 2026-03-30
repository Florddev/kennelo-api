"use client";

import { cn } from "@workspace/ui/lib/utils";
import type { LucideIcon } from "lucide-react";
import { ImageOff } from "lucide-react";
import { useId } from "react";

type ShapeMediaProps = {
    imageUrl?: string | null;
    className?: string;
    shapeClassName?: string;
    shapePathClassName?: string;
    badgeIcon?: LucideIcon;
    badgeContainerClassName?: string;
    badgeCircleClassName?: string;
    badgeIconClassName?: string;
    centerIcon?: LucideIcon;
    centerIconClassName?: string;
    centerIconSize?: number;
    showCenterIconWhenImage?: boolean;
    emptyIcon?: LucideIcon;
    emptyIconClassName?: string;
    emptyIconSize?: number;
    showEmptyIcon?: boolean;
    asButton?: boolean;
    onClick?: () => void;
    disabled?: boolean;
    type?: "button" | "submit" | "reset";
};

const SHAPE_VIEWBOX = "0 0 131 137";
const SHAPE_WIDTH = 131;
const SHAPE_HEIGHT = 137;
const SHAPE_PATH =
    "M130.981 61.8082C130.893 57.8746 130.536 53.9716 129.911 50.1604C128.742 43.0432 126.561 36.0561 122.8 29.8572C117.79 21.6035 111.737 22.3573 103.369 24.5537C95.6941 26.5703 87.4292 25.8203 80.0958 22.8739C76.3889 21.3854 72.9081 19.3306 69.8375 16.7745C65.9428 13.5297 63.9417 8.78104 60.2654 5.49411C54.4195 0.270984 46.5379 -0.119315 39.0934 0.0222643C30.4605 0.186802 20.1792 2.17656 13.1832 7.49535C6.69701 12.4315 2.59524 20.1303 0.87019 27.9784C0.479179 29.7577 0.210839 31.5676 0.0690018 33.3852V33.4656C-0.0230006 34.6326 -0.0230006 35.8074 0.0690018 36.9744V112.333C0.0690018 126.066 11.5808 136.945 25.3237 136.317C41.3436 135.586 57.9616 137.775 74.0351 135.353C97.4804 131.821 117.161 115.876 125.614 93.8013C129.482 83.7032 131.215 72.5988 130.977 61.812L130.981 61.8082Z";

function ShapeBadge({
    BadgeIcon,
    badgeContainerClassName,
    badgeCircleClassName,
    badgeIconClassName,
}: {
    BadgeIcon?: LucideIcon;
    badgeContainerClassName?: string;
    badgeCircleClassName?: string;
    badgeIconClassName?: string;
}) {
    if (!BadgeIcon) {
        return null;
    }

    return (
        <div
            className={cn(
                "absolute translate-x-1/2 -translate-y-1/2 top-1/32 right-3/10",
                badgeContainerClassName,
            )}
        >
            <svg
                width={33}
                height={32}
                viewBox="0 0 33 32"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className={cn("text-muted", badgeCircleClassName)}
            >
                <path
                    d="M1.10249 8.16558C-0.814231 12.8262 -0.147215 18.4741 2.44802 22.7482C5.04326 27.0224 9.40954 29.9229 14.0825 30.9981C18.2954 31.9662 22.8879 31.4764 26.5795 29.1078C35.1971 23.5748 34.3192 10.8364 26.9245 4.59931C19.5068 -1.65696 5.48027 -2.49113 1.10249 8.16558Z"
                    fill="currentColor"
                />
            </svg>
            <BadgeIcon
                className={cn(
                    "absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-4",
                    badgeIconClassName,
                )}
            />
        </div>
    );
}

function ShapeImage({
    imageUrl,
    patternId,
    shapeClassName,
    shapePathClassName,
}: {
    imageUrl?: string | null;
    patternId: string;
    shapeClassName?: string;
    shapePathClassName?: string;
}) {
    return (
        <svg
            width={SHAPE_WIDTH}
            height={SHAPE_HEIGHT}
            fill="none"
            viewBox={SHAPE_VIEWBOX}
            xmlns="http://www.w3.org/2000/svg"
            className={cn("text-muted", shapeClassName)}
        >
            {imageUrl && (
                <defs>
                    <pattern
                        id={patternId}
                        patternUnits="userSpaceOnUse"
                        width={SHAPE_WIDTH}
                        height={SHAPE_HEIGHT}
                    >
                        <image
                            href={imageUrl}
                            width={SHAPE_WIDTH}
                            height={SHAPE_HEIGHT}
                            preserveAspectRatio="xMidYMid slice"
                        />
                    </pattern>
                </defs>
            )}
            <path
                d={SHAPE_PATH}
                fill={imageUrl ? `url(#${patternId})` : "currentColor"}
                className={shapePathClassName}
            />
        </svg>
    );
}

function ShapeCenterIcon({
    Icon,
    className,
    size,
}: {
    Icon?: LucideIcon;
    className?: string;
    size: number;
}) {
    if (!Icon) {
        return null;
    }

    return (
        <Icon
            className={cn("absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2", className)}
            size={size}
        />
    );
}

function ShapeEmptyIcon({
    Icon,
    className,
    size,
}: {
    Icon: LucideIcon;
    className?: string;
    size: number;
}) {
    return (
        <Icon
            className={cn(
                "absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 mt-1 mr-4 text-muted-foreground opacity-10",
                className,
            )}
            size={size}
        />
    );
}

function valueOr<T>(value: T | undefined, fallback: T) {
    if (value === undefined) {
        return fallback;
    }

    return value;
}

function shouldShowCenterIcon(
    icon: LucideIcon | undefined,
    imageUrl: string | null | undefined,
    showCenterIconWhenImage: boolean,
) {
    if (!icon) {
        return false;
    }

    if (!imageUrl) {
        return true;
    }

    return showCenterIconWhenImage;
}

function shouldShowEmptyIcon(
    imageUrl: string | null | undefined,
    showEmptyIcon: boolean,
    showCenterIcon: boolean,
) {
    if (imageUrl) {
        return false;
    }

    if (!showEmptyIcon) {
        return false;
    }

    return !showCenterIcon;
}

export function ShapeMedia(props: ShapeMediaProps) {
    const patternId = useId().replace(/:/g, "");

    const imageUrl = props.imageUrl;
    const className = props.className;
    const shapeClassName = props.shapeClassName;
    const shapePathClassName = props.shapePathClassName;
    const BadgeIcon = props.badgeIcon;
    const badgeContainerClassName = props.badgeContainerClassName;
    const badgeCircleClassName = props.badgeCircleClassName;
    const badgeIconClassName = props.badgeIconClassName;
    const CenterIcon = props.centerIcon;
    const centerIconClassName = props.centerIconClassName;
    const centerIconSize = valueOr(props.centerIconSize, 18);
    const showCenterIconWhenImage = valueOr(props.showCenterIconWhenImage, false);
    const EmptyIcon = props.emptyIcon ?? ImageOff;
    const emptyIconClassName = props.emptyIconClassName;
    const emptyIconSize = valueOr(props.emptyIconSize, 40);
    const showEmptyIcon = valueOr(props.showEmptyIcon, true);
    const asButton = valueOr(props.asButton, false);
    const onClick = props.onClick;
    const disabled = props.disabled;
    const type = props.type ?? "button";

    const showCenterIcon = shouldShowCenterIcon(CenterIcon, imageUrl, showCenterIconWhenImage);
    const showEmptyStateIcon = shouldShowEmptyIcon(imageUrl, showEmptyIcon, showCenterIcon);

    const content = (
        <>
            <ShapeBadge
                BadgeIcon={BadgeIcon}
                badgeContainerClassName={badgeContainerClassName}
                badgeCircleClassName={badgeCircleClassName}
                badgeIconClassName={badgeIconClassName}
            />
            <ShapeImage
                imageUrl={imageUrl}
                patternId={patternId}
                shapeClassName={shapeClassName}
                shapePathClassName={shapePathClassName}
            />
            {showCenterIcon ? (
                <ShapeCenterIcon
                    Icon={CenterIcon}
                    className={centerIconClassName}
                    size={centerIconSize}
                />
            ) : null}
            {showEmptyStateIcon ? (
                <ShapeEmptyIcon
                    Icon={EmptyIcon}
                    className={emptyIconClassName}
                    size={emptyIconSize}
                />
            ) : null}
        </>
    );

    if (asButton) {
        return (
            <button
                data-slot="shape-media"
                type={type}
                className={cn("relative inline-flex items-center justify-center", className)}
                onClick={onClick}
                disabled={disabled}
            >
                {content}
            </button>
        );
    }

    return (
        <div
            data-slot="shape-media"
            className={cn("relative inline-flex items-center justify-center", className)}
            onClick={onClick}
        >
            {content}
        </div>
    );
}
