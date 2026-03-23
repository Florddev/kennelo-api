import AppLayout from "@/components/layouts/app-layout";

export default async function AppSpaceLayout({ children }: { children: React.ReactNode }) {
    return <AppLayout>{children}</AppLayout>;
}
