import { getTranslations } from "next-intl/server";
import { LegalArticle } from "@/components/layouts/legal-layout";

export default async function ConfidentialitePage() {
    const t = await getTranslations();

    return (
        <LegalArticle title={t("features.legal.confidentialite.title")}>
            <p>Dernière mise à jour : juillet 2026.</p>

            <section>
                <h2>1. Responsable du traitement</h2>
                <p>
                    Le responsable du traitement des données à caractère personnel collectées sur
                    Kennelo est la société éditrice de la plateforme, contactable à l&apos;adresse
                    indiquée sur la page Contact.
                </p>
            </section>

            <section>
                <h2>2. Données collectées</h2>
                <ul>
                    <li>Données d&apos;identification : nom, prénom, email, téléphone</li>
                    <li>Données de connexion : mot de passe (chiffré), historique de connexion</li>
                    <li>Données relatives aux animaux et aux réservations</li>
                    <li>Données de paiement traitées par notre prestataire Stripe</li>
                    <li>Données techniques : adresse IP, type d&apos;appareil, cookies</li>
                </ul>
            </section>

            <section>
                <h2>3. Finalités du traitement</h2>
                <p>
                    Ces données sont collectées pour permettre la création et la gestion du compte
                    utilisateur, la mise en relation entre hôtes et propriétaires d&apos;animaux, le
                    traitement des paiements, la sécurisation des comptes (authentification, lutte
                    contre la fraude) et l&apos;amélioration du service.
                </p>
            </section>

            <section>
                <h2>4. Base légale</h2>
                <p>
                    Les traitements reposent sur l&apos;exécution du contrat liant
                    l&apos;utilisateur à Kennelo, sur le respect d&apos;obligations légales, et sur
                    le consentement de l&apos;utilisateur pour les cookies non essentiels et les
                    communications marketing.
                </p>
            </section>

            <section>
                <h2>5. Durée de conservation</h2>
                <p>
                    Les données sont conservées pendant la durée de vie du compte utilisateur, puis
                    archivées ou supprimées conformément aux durées légales de conservation
                    applicables (notamment en matière comptable et fiscale).
                </p>
            </section>

            <section>
                <h2>6. Vos droits</h2>
                <p>
                    Conformément au Règlement Général sur la Protection des Données (RGPD) et à la
                    loi Informatique et Libertés, vous disposez d&apos;un droit d&apos;accès, de
                    rectification, d&apos;effacement, de portabilité, de limitation et
                    d&apos;opposition concernant vos données personnelles. Vous pouvez exercer ces
                    droits en nous contactant depuis la page Contact.
                </p>
                <p>
                    Vous disposez également du droit d&apos;introduire une réclamation auprès de la
                    Commission Nationale de l&apos;Informatique et des Libertés (CNIL).
                </p>
            </section>

            <section>
                <h2>7. Sécurité</h2>
                <p>
                    Kennelo met en œuvre des mesures techniques et organisationnelles appropriées
                    pour protéger vos données : chiffrement des mots de passe, connexion sécurisée,
                    authentification à deux facteurs, limitation des tentatives de connexion.
                </p>
            </section>

            <section>
                <h2>8. Cookies</h2>
                <p>
                    Kennelo utilise des cookies strictement nécessaires au fonctionnement du site
                    ainsi que, sous réserve de votre consentement, des cookies de mesure
                    d&apos;audience. Vous pouvez à tout moment modifier vos préférences via le
                    bandeau de gestion des cookies affiché sur le site.
                </p>
            </section>
        </LegalArticle>
    );
}
