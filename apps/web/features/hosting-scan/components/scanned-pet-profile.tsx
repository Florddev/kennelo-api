"use client";

import { useTranslations } from "next-intl";

import type { BookingModel } from "@workspace/modules/bookings";
import type { PetModel } from "@workspace/modules/pets";
import type { ScanOwnerModel } from "@workspace/modules/scanners";
import { Badge } from "@workspace/ui/components/badge";
import { Sticky } from "@workspace/ui/components/sticky";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { ScannedPetInfoTab } from "./scanned-pet-info-tab";
import { ScannedPetOwnerCard } from "./scanned-pet-owner-card";
import { ScannedPetStayTab } from "./scanned-pet-stay-tab";

export function ScannedPetProfile({
    pet,
    owner,
    ageDisplay,
    currentBooking,
    pastBookings,
}: {
    pet: PetModel;
    owner: ScanOwnerModel | null;
    ageDisplay: string | null;
    currentBooking: BookingModel | null;
    pastBookings: BookingModel[];
}) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center gap-3 px-4 pt-4">
                <div className="flex w-full flex-col">
                    <h1 className="text-3xl font-bold tracking-tight">{pet.name}</h1>
                    <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                        {pet.animalType && <span>{pet.animalType.name}</span>}
                        {pet.getBreedLabel() && (
                            <>
                                {pet.animalType && <span>·</span>}
                                <span>{pet.getBreedLabel()}</span>
                            </>
                        )}
                    </div>
                </div>
                <PetTypeIllustration
                    code={pet.animalType?.code ?? ""}
                    name={pet.name}
                    className="size-12"
                />
            </div>

            <div className="flex flex-wrap gap-2 px-4">
                {pet.sex !== "unknown" && (
                    <Badge variant="outline" className="capitalize">
                        {t(`features.pets.sex.${pet.sex}`)}
                    </Badge>
                )}
                {ageDisplay && <Badge variant="outline">{ageDisplay}</Badge>}
                {pet.weight && <Badge variant="outline">{pet.weight} kg</Badge>}
                {pet.isSterilized && (
                    <Badge variant="outline">{t("features.pets.badges.sterilized")}</Badge>
                )}
                {pet.hasMicrochip && (
                    <Badge variant="outline">{t("features.pets.badges.microchipped")}</Badge>
                )}
            </div>

            <Tabs defaultValue="stay" className="flex flex-col gap-0">
                <Sticky
                    top={0}
                    className="z-20 w-full bg-card"
                    stickyClassName="border-b border-border/30"
                >
                    <TabsList variant="line" className="w-full">
                        <div className="grid w-full grid-cols-3 px-4 py-2">
                            <div className="flex justify-start">
                                <TabsTrigger value="stay" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.hosting-scan.detail.tabs.stay")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                            <div className="flex justify-center">
                                <TabsTrigger value="pet" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.hosting-scan.detail.tabs.pet")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                            <div className="flex justify-end">
                                <TabsTrigger value="owner" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.hosting-scan.detail.tabs.owner")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                        </div>
                    </TabsList>
                </Sticky>

                <TabsContent value="stay" className="p-4">
                    <ScannedPetStayTab
                        currentBooking={currentBooking}
                        pastBookings={pastBookings}
                    />
                </TabsContent>

                <TabsContent value="pet" className="p-4">
                    <ScannedPetInfoTab pet={pet} ageDisplay={ageDisplay} />
                </TabsContent>

                <TabsContent value="owner" className="p-4">
                    {owner ? (
                        <ScannedPetOwnerCard owner={owner} />
                    ) : (
                        <p className="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                            {t("features.hosting-scan.detail.noOwner")}
                        </p>
                    )}
                </TabsContent>
            </Tabs>
        </div>
    );
}
