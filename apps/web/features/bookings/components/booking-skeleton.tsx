import { Skeleton } from "@workspace/ui/components/skeleton";

export function BookingSkeleton() {
    return (
        <div className="flex flex-col gap-4 p-4">
            <Skeleton className="h-12 w-full" />
            <Skeleton className="h-20 w-full rounded-2xl" />
            <Skeleton className="h-32 w-full rounded-2xl" />
            <Skeleton className="h-40 w-full rounded-2xl" />
        </div>
    );
}
