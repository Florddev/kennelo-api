import GuestLayout from "@/components/layouts/guest-layout";

export default async function SpacesAppLayout({ children }: { children: React.ReactNode }) {
    return <GuestLayout>{children}</GuestLayout>;
}
