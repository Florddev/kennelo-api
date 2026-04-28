import { cn } from "@workspace/ui/lib/utils";

export function StatusDot({
    isCancelled,
    showAnimation = true,
}: {
    isCancelled: boolean;
    showAnimation?: boolean;
}) {
    return (
        <span className="relative flex size-1.5">
            {showAnimation && (
                <span
                    className={cn(
                        "absolute inline-flex h-full w-full animate-ping rounded-full",
                        isCancelled ? "bg-red-300" : "bg-emerald-300",
                    )}
                />
            )}
            <span
                className={cn(
                    "relative inline-flex size-1.5 rounded-full",
                    isCancelled ? "bg-red-500" : "bg-emerald-500",
                )}
            />
        </span>
    );
}
