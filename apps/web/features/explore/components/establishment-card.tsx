import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { Image as ImageIcon } from "lucide-react";
import { EstablishmentModel } from "@workspace/modules/establishments";
import { cn } from "@workspace/ui/lib/utils";

type EstablishmentCardProps = {
    establishment: EstablishmentModel;
    href: string;
    className?: string;
};

export function EstablishmentCard({ establishment, href, className }: EstablishmentCardProps) {
    const t = useTranslations();
    const address = establishment.address;
    const subtitle = address ? `${address.city}, ${address.country}` : "";
    const imageUrl = establishment.getAvatarUrl();

    return (
        <Link
            href={href}
            data-slot="establishment-card"
            className={cn(
                "block overflow-hidden rounded-2xl bg-card transition-shadow hover:shadow-lg",
                className,
            )}
        >
            <div className="relative h-64 w-full overflow-hidden rounded-2xl bg-muted">
                {imageUrl ? (
                    <Image
                        src={imageUrl}
                        alt={establishment.name}
                        fill
                        className="object-cover"
                        sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center">
                        <div className="flex flex-col items-center gap-2 text-muted-foreground">
                            <ImageIcon className="size-10" />
                            <span className="text-xs">
                                {t("features.explore.noImageAvailable")}
                            </span>
                        </div>
                    </div>
                )}
            </div>
            <div className="flex flex-col gap-2 px-3 py-4">
                <h3 className="text-xl font-semibold text-foreground line-clamp-1">
                    {establishment.name}
                </h3>
                {subtitle && (
                    <p className="text-[15px] font-medium text-muted-foreground line-clamp-1">
                        {subtitle}
                    </p>
                )}
                {establishment.description && (
                    <p className="text-sm text-muted-foreground line-clamp-2">
                        {establishment.description}
                    </p>
                )}
            </div>
        </Link>
    );
}
