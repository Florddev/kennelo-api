import { getTranslations } from "next-intl/server";
import ActivityCapacitiesPage from "./activity-capacities-page";

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
        title: t("features.activities.capacities.title"),
    };
}

export default function ActivityCapacities() {
    return <ActivityCapacitiesPage />;
}
