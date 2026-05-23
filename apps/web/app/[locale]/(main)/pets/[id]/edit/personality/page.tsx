import { PetEditPersonalityPage } from "./personality-edit-page";

export function generateStaticParams() {
    return [{ id: "[id]" }];
}

export default function PetEditPersonality() {
    return <PetEditPersonalityPage />;
}
