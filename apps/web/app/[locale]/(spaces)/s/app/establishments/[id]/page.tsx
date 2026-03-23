import { getTranslations } from "next-intl/server";
import EstablishmentDetailPage from "./establishment-detail-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.my-establishments.title"),
    };
}

export default function EstablishmentDetail() {
    return <EstablishmentDetailPage />;
}
