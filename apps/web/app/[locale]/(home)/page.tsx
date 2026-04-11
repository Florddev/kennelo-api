"use client";

import HomeHero from "./sections/hero";
import Image from "next/image";

export default function Home() {
    return (
        <>
            <Image
                className="absolute top-0 left-0 w-168"
                src="/left-shape.svg"
                width={120}
                height={100}
                alt="Kennelo logo"
            />
            <div className="px-8">
                <HomeHero />
            </div>
        </>
    );
}
