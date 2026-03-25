import { useAuth } from "@/features/auth";
import { useLocale } from "next-intl";

const LOCALE_TO_COUNTRY: Record<string, string> = {
    en: "GB",
    fr: "FR",
    ar: "EG",
};

export function usePhoneCountryCode(): string {
    const { user } = useAuth();
    const locale = useLocale();

    if (user?.address?.country) {
        return user.address.country;
    }

    return LOCALE_TO_COUNTRY[locale] || "GB";
}
