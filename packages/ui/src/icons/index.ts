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
export * from "./message";
export * from "./heart";
export * from "./arrow-left-circle";
export * from "./calendar";
export * from "./key-2";
export * from "./locked-2";
export * from "./envelope-1";
export * from "./caravan-2";
export * from "./hand-taking-heart";
export * from "./dna-3";
export * from "./home-heart";
export * from "./fence-2";
export * from "./apartment-5";
export * from "./apartment-11";
export * from "./star-user";
export * from "./user-star";
export * from "./family-heart";
export * from "./nurse-hat-1";
export * from "./caravan-3";
export * from "./info-circle";
export * from "./heart-beat";
