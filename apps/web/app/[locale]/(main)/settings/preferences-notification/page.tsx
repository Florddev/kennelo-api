"use client";

import { Button } from "@workspace/ui/components/button";
import { Label } from "@workspace/ui/components/label";
import { Separator } from "@workspace/ui/components/separator";
import { Switch } from "@workspace/ui/components/switch";
import { useTranslations } from "next-intl";
import { Fragment } from "react";

const notifGroups = [
    {
        title: "Notifications push",
        description: "Notifications envoyées directement sur votre appareil.",
        items: [
            {
                id: "push-messages",
                label: "Nouveaux messages",
                description: "Quand vous recevez un message privé.",
                defaultChecked: true,
            },
            {
                id: "push-mentions",
                label: "Mentions",
                description: "Quand quelqu'un vous mentionne dans une publication.",
                defaultChecked: true,
            },
            {
                id: "push-followers",
                label: "Nouveaux abonnés",
                description: "Quand quelqu'un commence à vous suivre.",
                defaultChecked: false,
            },
            {
                id: "push-likes",
                label: "J'aime & réactions",
                description: "Quand quelqu'un réagit à votre contenu.",
                defaultChecked: false,
            },
        ],
    },
    {
        title: "Notifications in-app",
        description: "Notifications affichées directement dans l'application.",
        items: [
            {
                id: "inapp-messages",
                label: "Nouveaux messages",
                description: "Afficher une notification pour chaque message reçu.",
                defaultChecked: true,
            },
            {
                id: "inapp-activity",
                label: "Activité sur vos publications",
                description: "Commentaires, partages et réactions.",
                defaultChecked: true,
            },
            {
                id: "inapp-reminders",
                label: "Rappels",
                description: "Rappels pour les événements et activités à venir.",
                defaultChecked: true,
            },
            {
                id: "inapp-updates",
                label: "Mises à jour système",
                description: "Informations sur les nouvelles fonctionnalités et maintenances.",
                defaultChecked: false,
            },
        ],
    },
    {
        title: "Fréquence & disponibilité",
        description: "Contrôlez quand et combien de notifications vous recevez.",
        items: [
            {
                id: "digest",
                label: "Résumé quotidien",
                description:
                    "Recevoir un résumé de l'activité une fois par jour au lieu de notifications individuelles.",
                defaultChecked: false,
            },
            {
                id: "quiet-hours",
                label: "Heures silencieuses",
                description: "Désactiver les notifications entre 22h00 et 8h00.",
                defaultChecked: true,
            },
        ],
    },
];

export default function MyProfilePreferencesNotification() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6">
            {notifGroups.map((group, index) => (
                <Fragment key={group.title}>
                    <section className="grid grid-cols-[1fr_3fr] gap-8">
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

                    {index < notifGroups.length - 1 && <Separator />}
                </Fragment>
            ))}

            <div className="flex justify-start">
                <Button>{t("common.actions.save")}</Button>
            </div>
        </div>
    );
}
