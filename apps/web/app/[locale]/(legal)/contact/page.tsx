import { getTranslations } from "next-intl/server";
import ContactPage from "./contact-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.legal.contact.title"),
    };
}

export default function Contact() {
    return <ContactPage />;
}
