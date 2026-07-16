"use client";

import HomeHero from "./sections/hero";
import ExploreSpecies from "./sections/explore-species";
import HowItWorks from "./sections/how-it-works";
import WhyKennelo from "./sections/why-kennelo";
import KenneloScan from "./sections/kennelo-scan";
import BecomeHostCta from "./sections/become-host-cta";

export default function Home() {
    return (
        <div className="relative z-10 px-8 flex flex-col gap-12 pb-20">
            <HomeHero />
            <ExploreSpecies />
            <HowItWorks />
            <WhyKennelo />
            <KenneloScan />
            <BecomeHostCta />
        </div>
    );
}
