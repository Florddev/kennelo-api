import { getTranslations } from "next-intl/server";
import EstablishmentInfoPage from "./establishment-info-page";

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
        title: t("features.my-establishments.title"),
    };
}

export default function EstablishmentDetail() {
    return <EstablishmentInfoPage />;
}
