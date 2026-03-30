import AppLayout from "@/components/layouts/app-layout";
import { Separator } from "@workspace/ui/components/separator";

export default async function MainAppLayout({ children }: { children: React.ReactNode }) {
    return (
        <AppLayout>
            <Separator />
            <div className="w-full bg-card">
                <div className="container mx-auto w-full pb-12 sm:pb-0">{children}</div>
            </div>
        </AppLayout>
    );
}
