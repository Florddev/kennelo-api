"use client";

import { useState } from "react";
import Image from "next/image";
import { useAuth } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { SearchTrigger } from "@/features/explore/components/search-trigger";
import { FilterChips } from "@/features/explore/components/filter-chips";
import { ExploreSection } from "@/features/explore/components/explore-section";
import { SearchModal } from "@/features/explore/components/search-modal";
import {
    MOCK_NEARBY,
    MOCK_WEEKEND,
    MOCK_PROS,
    MOCK_PARTICULIERS,
    MOCK_NEW,
} from "@/features/explore/lib/mock-hosts";
import { Shield, CheckCircle, CreditCard, Headphones } from "lucide-react";

const TRUST_ITEMS = [
    { icon: Shield, label: "Assurance incluse" },
    { icon: CheckCircle, label: "Hôtes vérifiés" },
    { icon: CreditCard, label: "Paiement sécurisé" },
    { icon: Headphones, label: "Support 7j/7" },
];

const HOW_IT_WORKS = [
    { step: "1", title: "Cherchez", desc: "Trouvez l'hôte idéal près de chez vous" },
    { step: "2", title: "Réservez", desc: "Confirmez en quelques clics" },
    { step: "3", title: "Profitez", desc: "Votre animal est entre de bonnes mains" },
];

export default function ExplorePage() {
    const { user, isAuthenticated } = useAuth();
    const [activeFilter, setActiveFilter] = useState("all");
    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <div className="flex flex-col bg-background min-h-full">
            <header className="sticky top-0 z-10 bg-background/90 backdrop-blur-sm border-b border-border/40 px-4 py-3 flex items-center justify-between">
                <Image
                    src="/logo_font.svg"
                    height={24}
                    width={80}
                    alt="Kennelo"
                    className="h-6 w-auto"
                />
                <button className="size-9 rounded-full bg-muted flex items-center justify-center">
                    {isAuthenticated ? (
                        <UserAvatar user={user} className="size-8" />
                    ) : (
                        <span className="text-sm font-semibold text-muted-foreground">P</span>
                    )}
                </button>
            </header>

            <div className="px-4 pt-4 pb-3">
                <SearchTrigger onClick={() => setIsModalOpen(true)} />
            </div>

            <div className="pb-2">
                <FilterChips activeFilter={activeFilter} onSelect={setActiveFilter} />
            </div>

            <div className="flex flex-col gap-8 pb-8 pt-4">
                <ExploreSection title="Hôtes proches de chez vous" hosts={MOCK_NEARBY} />
                <ExploreSection title="Disponibles ce week-end" hosts={MOCK_WEEKEND} />
                <ExploreSection title="Les pros vérifiés près de chez vous" hosts={MOCK_PROS} />
                <ExploreSection title="Pet-sitters coup de cœur" hosts={MOCK_PARTICULIERS} />
                <ExploreSection title="Nouveaux hôtes dans votre région" hosts={MOCK_NEW} />

                <section className="mx-4">
                    <div className="bg-muted/40 rounded-3xl p-5">
                        <h2 className="text-lg font-bold mb-1">Première fois sur Kennelo ?</h2>
                        <p className="text-sm text-muted-foreground mb-5">
                            {"Trouver un hôte de confiance, c'est simple."}
                        </p>
                        <div className="flex flex-col gap-4">
                            {HOW_IT_WORKS.map((item) => (
                                <div key={item.step} className="flex items-start gap-3">
                                    <div className="size-8 rounded-full bg-secondary flex items-center justify-center shrink-0">
                                        <span className="text-sm font-bold text-secondary-foreground">
                                            {item.step}
                                        </span>
                                    </div>
                                    <div>
                                        <div className="text-sm font-semibold">{item.title}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {item.desc}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="mx-4">
                    <div className="grid grid-cols-2 gap-3">
                        {TRUST_ITEMS.map(({ icon: Icon, label }) => (
                            <div
                                key={label}
                                className="flex items-center gap-2.5 bg-muted/40 rounded-2xl px-3 py-3"
                            >
                                <Icon className="size-4 text-secondary shrink-0" />
                                <span className="text-xs font-medium leading-tight">{label}</span>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="mx-4">
                    <div className="bg-foreground rounded-3xl p-5 text-background">
                        <h2 className="text-lg font-bold mb-1.5">Vous êtes hôte ?</h2>
                        <p className="text-sm text-background/70 mb-4">
                            Rejoignez notre communauté et accueillez des animaux près de chez vous.
                        </p>
                        <button className="bg-background text-foreground text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-background/90 transition-colors">
                            Devenir hôte
                        </button>
                    </div>
                </section>

                <footer className="px-4">
                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                        <button className="hover:text-foreground transition-colors">
                            À propos
                        </button>
                        <button className="hover:text-foreground transition-colors">Aide</button>
                        <button className="hover:text-foreground transition-colors">
                            Conditions
                        </button>
                        <button className="hover:text-foreground transition-colors">Contact</button>
                    </div>
                </footer>
            </div>

            <SearchModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
        </div>
    );
}
