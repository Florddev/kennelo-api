import { getTranslations } from "next-intl/server";
import BecomeHostPage from "./become-host-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.become-host.title"),
        description: t("features.become-host.description"),
    };
}

export default function BecomeHost() {
    return <BecomeHostPage />;
}
