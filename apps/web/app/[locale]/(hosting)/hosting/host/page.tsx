import { getTranslations } from "next-intl/server";
import MyEstablishmentsPage from "./my-establishments-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.my-establishments.title"),
        description: t("features.my-establishments.description"),
    };
}

export default function MyEstablishments() {
    return <MyEstablishmentsPage />;
}
