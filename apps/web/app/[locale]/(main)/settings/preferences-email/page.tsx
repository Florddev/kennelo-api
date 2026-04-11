"use client";

import { Button } from "@workspace/ui/components/button";
import { Label } from "@workspace/ui/components/label";
import { Separator } from "@workspace/ui/components/separator";
import { Switch } from "@workspace/ui/components/switch";
import { useTranslations } from "next-intl";

const emailGroups = [
    {
        title: "Activité du compte",
        description: "Notifications relatives à votre compte et à sa sécurité.",
        items: [
            {
                id: "security",
                label: "Alertes de sécurité",
                description: "Connexions depuis un nouvel appareil ou lieu inhabituels.",
                defaultChecked: true,
            },
            {
                id: "account-changes",
                label: "Modifications du compte",
                description:
                    "Changement d'adresse e-mail, mot de passe ou informations personnelles.",
                defaultChecked: true,
            },
        ],
    },
    {
        title: "Social",
        description: "Interactions avec d'autres utilisateurs sur la plateforme.",
        items: [
            {
                id: "new-follower",
                label: "Nouveaux abonnés",
                description: "Quand quelqu'un commence à vous suivre.",
                defaultChecked: true,
            },
            {
                id: "mentions",
                label: "Mentions",
                description: "Quand quelqu'un vous mentionne dans une publication.",
                defaultChecked: false,
            },
            {
                id: "messages",
                label: "Nouveaux messages",
                description: "Quand vous recevez un message privé.",
                defaultChecked: true,
            },
        ],
    },
    {
        title: "Mises à jour & actualités",
        description: "Restez informé des dernières nouvelles de la plateforme.",
        items: [
            {
                id: "product-updates",
                label: "Mises à jour produit",
                description: "Nouvelles fonctionnalités et améliorations.",
                defaultChecked: false,
            },
            {
                id: "newsletter",
                label: "Newsletter mensuelle",
                description: "Résumé mensuel des actualités et tendances.",
                defaultChecked: false,
            },
            {
                id: "tips",
                label: "Conseils & astuces",
                description: "Guides pour tirer le meilleur parti de la plateforme.",
                defaultChecked: false,
            },
        ],
    },
];

export default function MyProfileEmailPreferences() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6">
            {emailGroups.map((group, index) => (
                <>
                    <section key={group.title} className="grid grid-cols-[1fr_3fr] gap-8">
                        <div>
                            <h2 className="text-lg font-semibold">{group.title}</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {group.description}
                            </p>
                        </div>

                        <div className="space-y-5">
                            {group.items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-start justify-between gap-4"
                                >
                                    <div className="space-y-0.5">
                                        <Label
                                            htmlFor={item.id}
                                            className="text-sm font-medium cursor-pointer"
                                        >
                                            {item.label}
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            {item.description}
                                        </p>
                                    </div>
                                    <Switch id={item.id} defaultChecked={item.defaultChecked} />
                                </div>
                            ))}
                        </div>
                    </section>

                    {index < emailGroups.length - 1 && <Separator />}
                </>
            ))}

            <div className="flex justify-start">
                <Button>{t("common.actions.save")}</Button>
            </div>
        </div>
    );
}
