import GuestLayout from "@/components/layouts/guest-layout";
import { JSX } from "react";

export default async function SpacesAppLayout({
    children,
}: {
    children: React.ReactNode;
}): Promise<JSX.Element> {
    return <GuestLayout>{children}</GuestLayout>;
}
