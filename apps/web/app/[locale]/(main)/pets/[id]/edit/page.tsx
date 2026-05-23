import { PetEditGeneralPage } from "./general/general-edit-page";

export function generateStaticParams() {
    return [{ id: "[id]" }];
}

export default function PetEditPage() {
    return <PetEditGeneralPage />;
}
