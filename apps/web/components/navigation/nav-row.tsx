import { AltArrowRight } from "@solar-icons/react";
import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";
import { useTranslations } from "next-intl";
import Link from "next/link";

export function NavRow({
    icon: Icon,
    label,
    href,
    className,
    onClick,
    destructive,
    displayArrow = false,
    disabled = false,
    comingSoon = false,
}: {
    icon?: React.ComponentType<{ className?: string }>;
    label: string;
    href?: string;
    className?: string;
    onClick?: () => void;
    destructive?: boolean;
    displayArrow?: boolean;
    disabled?: boolean;
    comingSoon?: boolean;
}) {
    const t = useTranslations();

    className = cn(
        "flex items-center gap-3 py-3.5 px-1 w-full text-base transition-colors md:text-base",
        destructive ? "text-destructive" : "hover:text-primary",
        disabled && "opacity-50 pointer-events-none",
        className,
    );

    const content = (
        <>
            {Icon && <Icon className="size-6 md:size-6 shrink-0" />}
            <span className="flex-1 text-start font-base">{label}</span>
            {comingSoon && <Badge variant="destructive">{t("ui.navigation.comingSoon")}</Badge>}
            {displayArrow && (
                <AltArrowRight className="size-4 md:hidden text-muted-foreground shrink-0" />
            )}
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
