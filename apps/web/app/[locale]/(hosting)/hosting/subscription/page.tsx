import { getTranslations } from "next-intl/server";

import HostingSubscriptionPage from "./hosting-subscription-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.subscriptions.title"),
        description: t("features.subscriptions.description"),
    };
}

export default function HostingSubscription() {
    return <HostingSubscriptionPage />;
}
