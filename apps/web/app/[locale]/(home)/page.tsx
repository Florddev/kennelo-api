"use client";

import HomeHero from "./sections/hero";
import ExploreSpecies from "./sections/explore-species";
import HowItWorks from "./sections/how-it-works";
import WhyKennelo from "./sections/why-kennelo";
import KenneloScan from "./sections/kennelo-scan";
import BecomeHostCta from "./sections/become-host-cta";
import HomeFooter from "./sections/footer";
import Image from "next/image";

export default function Home() {
    return (
        <div className="px-4 flex flex-col gap-12 pb-20">
            <Image
                className="absolute top-0 left-0 w-168 z-0"
                src="/left-shape.svg"
                width={120}
                height={100}
                alt="Kennelo logo"
            />
            {/* <Image
                className="absolute -top-1/2 left-0 w-[200vw] z-10"
                src="/shape_1.svg"
                width={120}
                height={100}
                alt="Kennelo logo"
            /> */}
            <div className="md:container md:mx-auto flex flex-col gap-32 z-10">
                <div className="flex flex-col gap-22 z-10">
                    <HomeHero />
                    <ExploreSpecies />
                </div>
                <HowItWorks />
                <WhyKennelo />
                <KenneloScan />
                <BecomeHostCta />
            </div>
            <HomeFooter />
        </div>
    );
}
