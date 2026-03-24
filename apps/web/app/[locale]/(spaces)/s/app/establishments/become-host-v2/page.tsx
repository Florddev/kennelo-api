import { getTranslations } from "next-intl/server";
import BecomeHostV2Page from "./become-host-v2-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.become-host.title"),
        description: t("features.become-host.description"),
    };
}

export default function BecomeHostV2() {
    return <BecomeHostV2Page />;
}
