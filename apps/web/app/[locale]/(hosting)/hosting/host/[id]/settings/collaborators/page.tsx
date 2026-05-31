import { getTranslations } from "next-intl/server";
import EstablishmentCollaboratorsPage from "./establishment-collaborators-page";

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
        title: t("features.establishments.manager.collaborators.title"),
    };
}

export default function EstablishmentCollaborators() {
    return <EstablishmentCollaboratorsPage />;
}
