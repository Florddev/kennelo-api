import LegalLayout from "@/components/layouts/legal-layout";
import { JSX } from "react";

export default async function LegalGroupLayout({
    children,
}: {
    children: React.ReactNode;
}): Promise<JSX.Element> {
    return <LegalLayout>{children}</LegalLayout>;
}
