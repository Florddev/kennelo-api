import { PetEditHealthPage } from "./health-edit-page";

export function generateStaticParams() {
    return [{ id: "[id]" }];
}

export default function PetEditHealth() {
    return <PetEditHealthPage />;
}
