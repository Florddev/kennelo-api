import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { Slot } from "radix-ui";

import { cn } from "@workspace/ui/lib/utils";

const badgeVariants = cva(
    "gap-1 rounded-4xl border border-transparent text-xs font-medium transition-all has-data-[icon=inline-end]:pe-1.5 has-data-[icon=inline-start]:ps-1.5 [&>svg]:size-3! inline-flex items-center justify-center w-fit whitespace-nowrap shrink-0 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive overflow-hidden group/badge",
    {
        variants: {
            variant: {
                default: "bg-primary text-primary-foreground [a]:hover:bg-primary/80",
                secondary: "bg-secondary text-secondary-foreground [a]:hover:bg-secondary/80",
                destructive:
                    "bg-destructive/10 [a]:hover:bg-destructive/20 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 text-destructive dark:bg-destructive/20",
                outline:
                    "border-border text-foreground [a]:hover:bg-muted [a]:hover:text-muted-foreground bg-input/30",
                ghost: "hover:bg-muted hover:text-muted-foreground dark:hover:bg-muted/50",
                link: "text-primary underline-offset-4 hover:underline",
                flat: "bg-muted hover:bg-muted-foreground/15 aria-expanded:bg-muted aria-expanded:text-foreground font-semibold",
            },
            size: {
                xs: "h-4 px-1.5 py-0.5",
                sm: "h-5 px-2 py-0.5",
                default: "h-6 px-2.5 py-1",
                md: "h-7 px-3 py-1.5",
                lg: "h-8 px-3.5 py-2",
                icon: "size-6",
                "icon-sm": "size-5 [&_svg:not([class*='size-'])]:size-3",
                "icon-md": "size-7",
                "icon-lg": "size-8",
            },
        },
        defaultVariants: {
            variant: "default",
            size: "default",
        },
    },
);

function Badge({
    className,
    variant = "default",
    size = "default",
    asChild = false,
    ...props
}: React.ComponentProps<"span"> & VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
    const Comp = asChild ? Slot.Root : "span";

    return (
        <Comp
            data-slot="badge"
            data-variant={variant}
            data-size={size}
            className={cn(badgeVariants({ variant, size }), className)}
            {...props}
        />
    );
}

export { Badge, badgeVariants };
