import { EstablishmentSidebarLayout } from "@/features/establishments/components/establishment-sidebar-layout";

export default async function Layout({
    children,
    params,
}: {
    children: React.ReactNode;
    params: Promise<{ id: string }>;
}) {
    const { id } = await params;

    return <EstablishmentSidebarLayout establishmentId={id}>{children}</EstablishmentSidebarLayout>;
}
