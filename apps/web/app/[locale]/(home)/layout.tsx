import AppLayout from "@/components/layouts/app-layout";

export default async function Layout({ children }: { children: React.ReactNode }) {
    return <AppLayout>{children}</AppLayout>;
}
