import { getTranslations } from "next-intl/server";
import { LegalArticle } from "@/components/layouts/legal-layout";

export default async function CgvPage() {
    const t = await getTranslations();

    return (
        <LegalArticle title={t("features.legal.cgv.title")}>
            <p>Dernière mise à jour : juillet 2026.</p>

            <section>
                <h2>1. Objet</h2>
                <p>
                    Les présentes conditions générales de vente (CGV) s&apos;appliquent à toute
                    réservation de prestation de garde d&apos;animaux effectuée via la plateforme
                    Kennelo entre un hôte et un propriétaire d&apos;animal.
                </p>
            </section>

            <section>
                <h2>2. Prix et paiement</h2>
                <p>
                    Les prix des prestations sont fixés librement par chaque hôte et affichés en
                    euros, toutes taxes comprises. Le paiement est effectué en ligne au moment de la
                    réservation via notre prestataire de paiement sécurisé Stripe. Kennelo ne stocke
                    aucune donnée bancaire.
                </p>
            </section>

            <section>
                <h2>3. Commission de service</h2>
                <p>
                    Kennelo perçoit une commission sur chaque réservation confirmée, prélevée
                    automatiquement au moment du paiement. Le montant de cette commission est
                    indiqué avant la validation de la réservation.
                </p>
            </section>

            <section>
                <h2>4. Annulation et remboursement</h2>
                <p>
                    Les conditions d&apos;annulation sont précisées sur la page de chaque activité
                    avant la réservation. En cas d&apos;annulation par l&apos;hôte, le propriétaire
                    est intégralement remboursé.
                </p>
            </section>

            <section>
                <h2>5. Responsabilité</h2>
                <p>
                    Kennelo met en relation les utilisateurs mais n&apos;est pas partie au contrat
                    de garde conclu entre l&apos;hôte et le propriétaire de l&apos;animal.
                </p>
            </section>

            <section>
                <h2>6. Litiges</h2>
                <p>
                    En cas de litige relatif à une réservation, l&apos;utilisateur peut contacter le
                    support Kennelo. À défaut de résolution amiable, les tribunaux français seront
                    seuls compétents.
                </p>
            </section>
        </LegalArticle>
    );
}
