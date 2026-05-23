import { cn } from "@workspace/ui/lib/utils";

export function Frame({
    header,
    footer,
    children,
    className,
    contentClassName,
}: {
    header?: string | React.ReactNode;
    footer?: string | React.ReactNode;
    children: React.ReactNode;
    className?: string;
    contentClassName?: string;
}) {
    return (
        <div
            className={cn(
                "flex flex-col bg-muted rounded-sm p-0.5 relative overflow-hidden relative",
                className,
            )}
        >
            {header}
            <div className={cn("bg-card rounded-sm p-4 border", contentClassName)}>{children}</div>
            {footer}
        </div>
    );
}
