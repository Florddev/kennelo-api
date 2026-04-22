import { Skeleton } from "@workspace/ui/components/skeleton";

export function HostDetailSkeleton() {
    return (
        <div className="flex flex-col bg-white">
            <Skeleton className="aspect-[4/3] w-full rounded-none" />
            <div className="flex flex-col gap-4 px-4 pt-6">
                <Skeleton className="h-6 w-2/3" />
                <Skeleton className="h-4 w-1/2" />
                <Skeleton className="h-16 w-full rounded-2xl" />
                <Skeleton className="h-28 w-full rounded-3xl" />
                <Skeleton className="h-48 w-full rounded-3xl" />
            </div>
        </div>
    );
}
