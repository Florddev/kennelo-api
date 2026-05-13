import { User } from "@solar-icons/react";
import { UserModel } from "@workspace/modules/users";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";

export function UserAvatar({
    user,
    className,
    size,
}: {
    user?: UserModel | null;
    className?: string;
    size?: "default" | "sm" | "lg";
}) {
    return (
        <Avatar className={cn("cursor-pointer after:border-0", className)} size={size}>
            <AvatarImage
                src={user?.avatarUrl || undefined}
                alt={user?.getFullName() || "User profile"}
            />
            <AvatarFallback className="text-xs bg-muted border-0">
                <User
                    weight="Bold"
                    className="size-full max-w-2/3 text-muted-foreground/30 border-0"
                />
            </AvatarFallback>
        </Avatar>
    );
}
