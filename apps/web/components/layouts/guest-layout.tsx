"use client";

import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import Image from "next/image";
import { LanguageSwitcher } from "../i18n/language-switcher";
import Link from "next/link";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";

export default function GuestLayout({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    const isMobile = useIsMobile();
    const { routes } = useNavigation();
    const t = useTranslations();

    const isRtl = t("settings.dir") === "rtl";

    return (
        <div className="bg-background h-full overflow-hidden">
            {!isMobile && (
                <header className="fixed top-0 start-0 w-full h-16 flex items-center z-10">
                    <div className="container mx-auto max-w-7xl h-full flex justify-between items-center px-6">
                        <Link
                            href={routes.Home()}
                            className="relative w-full h-full flex justify-start items-center font-semibold text-lg"
                        >
                            <Image
                                className="object-cover max-h-full w-auto"
                                src="/logo_font.svg"
                                width={120}
                                height={100}
                                alt="Kennelo logo"
                            />
                        </Link>
                        <LanguageSwitcher showDetails />
                    </div>
                </header>
            )}
            <main className={cn("container mx-auto relative h-full", className)}>
                <div className="grid relative lg:grid-cols-2">
                    {/* <Image
                        className="absolute z-0 w-full rotate-90 -top-8/12 md:max-w-unset md:-right-1/12 md:-top-11/12 opacity-40"
                        src="/simple_shape_1.svg"
                        width={600}
                        height={600}
                        alt="Kennelo logo"
                    /> */}
                    <div className="relative inset-0 max-w-screen max-h-screen">
                        <div className="absolute flex flex-col justify-between items-center w-full h-full">
                            <div
                                className={cn(
                                    "relative w-full flex justify-center lg:h-[65%] translate-y-1/6 scale-[180%]",
                                    isRtl
                                        ? "lg:rotate-y-180 lg:-translate-x-1/4"
                                        : " lg:translate-x-1/4",
                                )}
                            >
                                <Image
                                    className="w-full max-w-md -translate-y-16 lg:max-w-full lg:h-full object-contain"
                                    src="/shape_2.svg"
                                    width={600}
                                    height={600}
                                    alt="Kennelo logo"
                                />
                            </div>
                            <div
                                className={cn(
                                    "relative hidden max-w-sm lg:flex w-full justify-center scale-[190%] lg:max-w-md xl:max-w-lg lg:h-[50%]",
                                    isRtl ? "lg:rotate-y-180" : "",
                                )}
                            >
                                <Image
                                    className="w-auto h-full object-contain lg:-translate-x-6 lg:-translate-y-2"
                                    src="/keny_illustration.svg"
                                    width={600}
                                    height={600}
                                    alt="Kennelo logo"
                                />
                            </div>
                        </div>
                    </div>
                    <div className="m-auto w-full flex items-center justify-center z-0 mt-42 sm:mt-20 md:mt-16 lg:mt-0">
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
