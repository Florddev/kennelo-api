"use client";

import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { addActivityFavorite, removeActivityFavorite } from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";
import { KHeart } from "@workspace/ui/icons";
import { cn } from "@workspace/ui/lib/utils";
import { useAuth } from "@/features/auth";

type FavoriteButtonProps = {
    activityId: string;
    isFavorited: boolean;
    className?: string;
};

export function FavoriteButton({ activityId, isFavorited, className }: FavoriteButtonProps) {
    const t = useTranslations();
    const { isAuthenticated } = useAuth();
    const [favorited, setFavorited] = useState(isFavorited);

    const mutation = useMutation({
        mutationFn: (next: boolean) =>
            next ? addActivityFavorite(activityId) : removeActivityFavorite(activityId),
        onError: (error: Error, next: boolean) => {
            setFavorited(!next);
            toast.error(t("features.explore.card.favoriteError"), { description: error.message });
        },
    });

    if (!isAuthenticated) {
        return null;
    }

    function handleClick(event: React.MouseEvent) {
        event.stopPropagation();
        event.preventDefault();
        const next = !favorited;
        setFavorited(next);
        mutation.mutate(next);
    }

    return (
        <Button
            type="button"
            size="icon-sm"
            variant="flat"
            onClick={handleClick}
            disabled={mutation.isPending}
            aria-label={
                favorited
                    ? t("features.explore.card.removeFromFavorites")
                    : t("features.explore.card.addToFavorites")
            }
            className={cn("rounded-full", favorited && "text-primary", className)}
        >
            <KHeart filled={favorited} size={18} />
        </Button>
    );
}
