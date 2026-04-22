import Image from "next/image";
import { useTranslations } from "next-intl";

export function HostVerifiedBanner() {
    const t = useTranslations();

    return (
        <div
            data-slot="host-verified-banner"
            className="relative flex items-center gap-3 overflow-hidden rounded-3xl bg-secondary/20 p-4"
        >
            <span
                aria-hidden
                className="pointer-events-none absolute -top-5 -start-5 h-20 w-24 rounded-[50%] bg-secondary"
            />
            <div className="relative flex flex-1 flex-col gap-2">
                <h3 className="text-lg font-semibold text-slate-900 whitespace-nowrap">
                    {t("features.host.detail.verifiedTitle")}
                </h3>
                <p className="text-xs text-slate-700">
                    {t("features.host.detail.verifiedDescription")}
                </p>
            </div>
            <Image
                src="/keny_illustration.png"
                alt=""
                aria-hidden
                width={122}
                height={92}
                className="relative h-auto w-28 shrink-0 object-contain"
            />
        </div>
    );
}
