import Image from "next/image";
import { useTranslations } from "next-intl";

export function VerifiedBanner() {
    const t = useTranslations();

    return (
        <div
            data-slot="verified-banner"
            className="relative flex items-center gap-3 overflow-hidden rounded-3xl bg-secondary/20 p-4"
        >
            <span
                aria-hidden
                className="pointer-events-none absolute -top-4 -start-4 h-16 w-20 rounded-[50%] bg-secondary"
            />
            <div className="relative flex flex-1 flex-col gap-2">
                <h3 className="text-lg font-semibold text-slate-900 whitespace-nowrap">
                    {t("features.explore.detail.verifiedTitle")}
                </h3>
                <p className="text-xs text-slate-700">
                    {t("features.explore.detail.verifiedDescription")}
                </p>
            </div>
            <Image
                src="/keny_illustration.svg"
                alt=""
                aria-hidden
                width={122}
                height={92}
                className="relative h-auto w-28 shrink-0 object-contain"
            />
        </div>
    );
}
