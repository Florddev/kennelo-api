import { JSX } from "react";
import PetDetailsPage from "./pet-details-page";

export type Query = {
    id: string;
};

export function generateStaticParams(): Query[] {
    return [{ id: "[id]" }];
}

export default function PetDetails(): JSX.Element {
    return <PetDetailsPage />;
}
