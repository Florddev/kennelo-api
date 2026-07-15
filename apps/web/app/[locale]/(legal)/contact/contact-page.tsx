import { getTranslations } from "next-intl/server";
import { LegalArticle } from "@/components/layouts/legal-layout";

const CONTACT_EMAIL = "contact@kennelo.fr";

export default async function ContactPage() {
    const t = await getTranslations();

    return (
        <LegalArticle title={t("features.legal.contact.title")}>
            <p>{t("features.legal.contact.intro")}</p>

            <section>
                <h2>{t("features.legal.contact.emailLabel")}</h2>
                <p>
                    <a href={`mailto:${CONTACT_EMAIL}`} className="text-primary hover:underline">
                        {CONTACT_EMAIL}
                    </a>
                </p>
            </section>
        </LegalArticle>
    );
}
