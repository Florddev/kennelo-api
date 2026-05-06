import { UserModel } from "@workspace/modules/users";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";

export function UserAvatar({ user, className }: { user?: UserModel | null; className?: string }) {
    return (
        <Avatar className={cn("cursor-pointer", className)}>
            <AvatarImage
                src={user?.avatarUrl || undefined}
                alt={user?.getFullName() || "User profile"}
            />
            <AvatarFallback className="text-xs">{user?.getInitials() || "U"}</AvatarFallback>
        </Avatar>
    );
}
