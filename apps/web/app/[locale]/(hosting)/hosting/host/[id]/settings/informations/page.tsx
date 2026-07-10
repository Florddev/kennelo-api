import { getTranslations } from "next-intl/server";
import ActivityInformationsPage from "./activity-informations-page";

export type Query = {
    id: string;
};

export function generateStaticParams(): Query[] {
    return [{ id: "[id]" }];
}

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.activities.manager.nav.info"),
    };
}

export default function ActivitySettingsInformations() {
    return <ActivityInformationsPage />;
}
