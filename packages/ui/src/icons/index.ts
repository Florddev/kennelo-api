import { ComponentType } from "react";

export type KIconProps = {
    filled?: boolean;
    size?: number;
    primary?: string;
    secondary?: string;
    secondaryOpacity?: number;
    className?: string;
};

export type KIcon = ComponentType<KIconProps>;

export * from "./compass";
export * from "./home";
export * from "./heart";
export * from "./calendar";
export * from "./hand-taking-heart";
export * from "./dna-3";
export * from "./home-heart";
export * from "./fence-2";
export * from "./apartment-5";
export * from "./user-star";
export * from "./family-heart";
export * from "./caravan-3";
