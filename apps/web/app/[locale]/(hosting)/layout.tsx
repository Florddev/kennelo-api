import HostingLayout from "@/components/layouts/hosting-layout";

export default async function Layout({ children }: { children: React.ReactNode }) {
    return (
        <HostingLayout>
            <div className="w-full h-full pb-12 sm:pb-0">{children}</div>
        </HostingLayout>
    );
}
