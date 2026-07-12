import { getTranslations } from "next-intl/server";
import SelectActivityPage from "./select-activity-page";
import HostingLayout from "../hosting-layout";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.activities.title"),
        description: t("features.activities.description"),
    };
}

export default function MyActivities() {
    return (
        <HostingLayout>
            <SelectActivityPage />
        </HostingLayout>
    );
}
