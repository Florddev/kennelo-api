import { AltArrowRight } from "@solar-icons/react";
import { cn } from "@workspace/ui/lib/utils";
import Link from "next/link";

export function NavRow({
    icon: Icon,
    badge,
    label,
    href,
    onClick,
    destructive,
    displayArrow = false,
    disabled = false,
}: {
    icon?: React.ComponentType<{ className?: string }>;
    badge?: React.ReactNode;
    label: string;
    href?: string;
    onClick?: () => void;
    destructive?: boolean;
    displayArrow?: boolean;
    disabled?: boolean;
}) {
    const className = cn(
        "flex items-center gap-3 py-3.5 px-0.5 w-full text-sm transition-colors",
        destructive ? "text-destructive" : "hover:text-primary",
        disabled && "opacity-50 pointer-events-none",
    );

    const content = (
        <>
            {Icon && <Icon className="size-4.5 shrink-0" />}
            <span className="flex-1 text-start font-base">{label}</span>
            {badge}
            {displayArrow && <AltArrowRight className="size-4 text-muted-foreground shrink-0" />}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return (
        <button onClick={onClick} className={className} disabled={disabled}>
            {content}
        </button>
    );
}
