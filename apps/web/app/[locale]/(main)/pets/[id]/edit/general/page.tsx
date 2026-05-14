import { PetEditGeneralPage } from "./general-edit-page";

export function generateStaticParams() {
    return [{ id: "[id]" }];
}

export default function PetEditGeneral() {
    return <PetEditGeneralPage />;
}
