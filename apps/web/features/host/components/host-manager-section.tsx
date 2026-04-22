import { useTranslations } from "next-intl";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import type { UserModel } from "@workspace/modules/users";

type HostManagerSectionProps = {
    manager: UserModel;
};

export function HostManagerSection({ manager }: HostManagerSectionProps) {
    const t = useTranslations();

    return (
        <section className="flex items-center gap-2 pt-4">
            <Avatar size="lg">
                {manager.avatarUrl && (
                    <AvatarImage src={manager.avatarUrl} alt={manager.getFullName()} />
                )}
                <AvatarFallback>{manager.getInitials()}</AvatarFallback>
            </Avatar>
            <div className="flex flex-1 flex-col py-0.5">
                <p className="text-sm font-medium text-black">
                    {t("features.host.detail.host", { name: manager.firstName })}
                </p>
                {manager.isIdVerified && (
                    <p className="text-xs text-emerald-600">
                        {t("features.host.detail.managerVerified")}
                    </p>
                )}
            </div>
        </section>
    );
}
