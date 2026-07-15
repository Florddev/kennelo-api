import { getTranslations } from "next-intl/server";
import CguPage from "./cgu-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.legal.cgu.title"),
    };
}

export default function Cgu() {
    return <CguPage />;
}
