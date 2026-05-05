import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { Button } from "@workspace/ui/components/button";
import { ArrowLeft } from "@solar-icons/react";

export function ConversationHeader({
    name,
    avatarUrl,
    onBack,
}: {
    name: string;
    avatarUrl?: string;
    onBack?: () => void;
}) {
    return (
        <div className="sticky top-0 z-10 flex flex-shrink-0 items-center gap-3 border-b bg-card px-4 py-3">
            {onBack && (
                <Button variant="ghost" size="icon-sm" onClick={onBack} className="-ms-1">
                    <ArrowLeft className="size-4" />
                </Button>
            )}
            <Avatar>
                {avatarUrl && <AvatarImage src={avatarUrl} alt={name} />}
                <AvatarFallback>{name.slice(0, 2).toUpperCase()}</AvatarFallback>
            </Avatar>
            <p className="flex-1 truncate text-sm font-semibold">{name}</p>
        </div>
    );
}
