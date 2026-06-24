"use client";

import { Skeleton } from "@workspace/ui/components/skeleton";

export function PetDetailSkeleton() {
    return (
        <div className="min-h-screen">
            <div className="absolute top-0 z-10 flex w-full items-center justify-between p-2">
                <Skeleton className="size-8 rounded-4xl" />
                <div className="flex gap-0.5">
                    <Skeleton className="size-8 rounded-4xl" />
                    <Skeleton className="size-8 rounded-4xl" />
                </div>
            </div>

            <div className="pb-20 sm:pb-6">
                <div className="flex flex-col gap-8 lg:flex-row">
                    <div className="min-w-0 flex-1 space-y-8">
                        <div className="space-y-6">
                            <div className="flex flex-col">
                                <Skeleton className="h-72 w-full rounded-none sm:rounded-3xl" />

                                <div className="z-10 -mt-6 rounded-3xl bg-card p-4 sm:mt-0 sm:px-0">
                                    <div className="flex flex-col gap-6">
                                        <div className="flex items-center gap-2">
                                            <div className="flex-1 space-y-2.5">
                                                <Skeleton className="h-9 w-44 rounded-xl" />
                                                <div className="flex items-center gap-2">
                                                    <Skeleton className="h-4 w-20 rounded-xl" />
                                                    <Skeleton className="h-4 w-24 rounded-xl" />
                                                </div>
                                            </div>
                                            <Skeleton className="size-12 rounded-full" />
                                        </div>

                                        <div className="space-y-3">
                                            <Skeleton className="h-6 w-28 rounded-xl" />
                                            <Skeleton className="h-4 w-full rounded-xl" />
                                            <Skeleton className="h-4 w-5/6 rounded-xl" />
                                            <div className="flex flex-wrap gap-2">
                                                <Skeleton className="h-7 w-20 rounded-4xl" />
                                                <Skeleton className="h-7 w-24 rounded-4xl" />
                                                <Skeleton className="h-7 w-18 rounded-4xl" />
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="grid grid-cols-3 gap-2 border-b pb-2">
                                                <Skeleton className="h-8 w-full rounded-xl" />
                                                <Skeleton className="h-8 w-full rounded-xl" />
                                                <Skeleton className="h-8 w-full rounded-xl" />
                                            </div>

                                            <div className="space-y-6">
                                                <div className="space-y-4 rounded-3xl">
                                                    <Skeleton className="h-6 w-40 rounded-xl" />
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                    </div>
                                                </div>

                                                <div className="space-y-3">
                                                    <Skeleton className="h-6 w-32 rounded-xl" />
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                        <Skeleton className="h-12 rounded-2xl" />
                                                    </div>
                                                    <Skeleton className="h-24 rounded-2xl" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
