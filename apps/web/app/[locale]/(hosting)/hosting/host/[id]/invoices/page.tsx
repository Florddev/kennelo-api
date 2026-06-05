import { getTranslations } from "next-intl/server";
import EstablishmentInvoicesPage from "./establishment-invoices-page";

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
        title: t("features.establishments.manager.nav.invoices"),
    };
}

export default function EstablishmentInvoices() {
    return <EstablishmentInvoicesPage />;
}
