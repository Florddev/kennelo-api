import { getTranslations } from "next-intl/server";
import { LegalArticle } from "@/components/layouts/legal-layout";

export default async function CguPage() {
    const t = await getTranslations();

    return (
        <LegalArticle title={t("features.legal.cgu.title")}>
            <p>Dernière mise à jour : juillet 2026.</p>

            <section>
                <h2>1. Objet</h2>
                <p>
                    Les présentes conditions générales d&apos;utilisation (CGU) régissent
                    l&apos;accès et l&apos;utilisation de la plateforme Kennelo, un service de mise
                    en relation entre particuliers pour la garde d&apos;animaux de compagnie. Toute
                    inscription ou utilisation du service implique l&apos;acceptation sans réserve
                    des présentes CGU.
                </p>
            </section>

            <section>
                <h2>2. Accès au service et création de compte</h2>
                <p>
                    L&apos;accès à certaines fonctionnalités nécessite la création d&apos;un compte
                    utilisateur (email et mot de passe, connexion via Google, ou lien de connexion
                    envoyé par email). L&apos;utilisateur s&apos;engage à fournir des informations
                    exactes et à maintenir la confidentialité de ses identifiants.
                </p>
                <p>
                    Pour renforcer la sécurité du compte, un mot de passe fort est requis et son
                    renouvellement est demandé périodiquement. Une authentification à deux facteurs
                    peut également être activée.
                </p>
            </section>

            <section>
                <h2>3. Obligations des utilisateurs</h2>
                <ul>
                    <li>Fournir des informations exactes sur soi-même et ses animaux</li>
                    <li>Respecter les autres utilisateurs et les animaux confiés</li>
                    <li>Ne pas utiliser la plateforme à des fins frauduleuses</li>
                    <li>Respecter la législation applicable en matière de bien-être animal</li>
                </ul>
            </section>

            <section>
                <h2>4. Responsabilité</h2>
                <p>
                    Kennelo agit en tant qu&apos;intermédiaire de mise en relation. Kennelo ne
                    saurait être tenu responsable des dommages survenus lors d&apos;une garde
                    organisée entre utilisateurs, en dehors des obligations légales qui incombent à
                    un hébergeur de service en ligne.
                </p>
            </section>

            <section>
                <h2>5. Propriété intellectuelle</h2>
                <p>
                    L&apos;ensemble des éléments de la plateforme (textes, logos, interface) est
                    protégé par le droit de la propriété intellectuelle et reste la propriété
                    exclusive de Kennelo.
                </p>
            </section>

            <section>
                <h2>6. Résiliation</h2>
                <p>
                    L&apos;utilisateur peut supprimer son compte à tout moment depuis son profil.
                    Kennelo se réserve le droit de suspendre ou résilier un compte en cas de
                    non-respect des présentes CGU.
                </p>
            </section>

            <section>
                <h2>7. Droit applicable</h2>
                <p>
                    Les présentes CGU sont soumises au droit français. Tout litige relève de la
                    compétence des tribunaux français.
                </p>
            </section>
        </LegalArticle>
    );
}
