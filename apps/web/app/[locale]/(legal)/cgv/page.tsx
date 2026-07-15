import { getTranslations } from "next-intl/server";
import CgvPage from "./cgv-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.legal.cgv.title"),
    };
}

export default function Cgv() {
    return <CgvPage />;
}
