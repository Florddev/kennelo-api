import { getTranslations } from "next-intl/server";
import HostDetailPage from "./host-detail-page";

export type Query = {
    id: string;
};

export function generateStaticParams(): Query[] {
    return [{ id: "[id]" }];
}

export async function generateMetadata({
    params,
}: {
    params: Promise<{ locale: string }>;
}) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.host.detail.title"),
    };
}

export default function HostDetail() {
    return <HostDetailPage />;
}
