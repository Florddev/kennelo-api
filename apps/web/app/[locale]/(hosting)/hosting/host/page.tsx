import { getTranslations } from "next-intl/server";
import SelectEstablishmentPage from "./select-establishment-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.establishments.title"),
        description: t("features.establishments.description"),
    };
}

export default function MyEstablishments() {
    return <SelectEstablishmentPage />;
}
