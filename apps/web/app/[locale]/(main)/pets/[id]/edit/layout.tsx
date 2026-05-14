import { type ReactNode } from "react";

import { PetEditLayout } from "./pet-edit-layout";

export default function Layout({ children }: { children: ReactNode }) {
    return <PetEditLayout>{children}</PetEditLayout>;
}
