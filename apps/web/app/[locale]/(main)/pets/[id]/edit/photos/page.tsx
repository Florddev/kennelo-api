import { PetEditPhotosPage } from "./photos-edit-page";

export function generateStaticParams() {
    return [{ id: "[id]" }];
}

export default function PetEditPhotos() {
    return <PetEditPhotosPage />;
}
