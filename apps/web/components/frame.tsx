import { cn } from "@workspace/ui/lib/utils";

export function Frame({
    title,
    children,
    className,
}: {
    title?: string | React.ReactNode;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                "flex flex-col bg-muted rounded-sm p-0.5 relative overflow-hidden",
                className,
            )}
        >
            {title}
            <div className="bg-card rounded-sm p-4 border">{children}</div>
        </div>
    );
}
