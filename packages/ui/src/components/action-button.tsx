import { cn } from "../lib/utils";
import { Button } from "./button";
import { ArrowUpRight } from "lucide-react";

export function ActionButton({
    className,
    children,
    icon,
}: {
    className?: string;
    children: React.ReactNode;
    icon?: React.ReactNode;
}) {
    return (
        <Button
            className={cn(
                "relative text-sm font-medium rounded-full h-12 p-1 ps-6 pe-14 group transition-all duration-500 hover:ps-14 hover:pe-6 w-fit overflow-hidden cursor-pointer hover:bg-unset",
                className,
            )}
        >
            <span className="relative z-0 transition-all duration-500">{children}</span>
            <div className="absolute right-1 aspect-square h-[calc(100%-8px)] bg-secondary text-secondary-foreground rounded-full flex items-center justify-center transition-all duration-500 group-hover:right-[calc(100%-4px)]  group-hover:translate-x-full group-hover:rotate-45 z-10">
                {icon || <ArrowUpRight size={16} />}
            </div>
        </Button>
    );
}
