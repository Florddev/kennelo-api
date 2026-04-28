import AppLayout from "@/components/layouts/app-layout";
import { Separator } from "@workspace/ui/components/separator";

export default async function Layout({ children }: { children: React.ReactNode }) {
    return (
        <AppLayout>
            <Separator />
            <div className="w-full h-full">
                <div className="w-full pb-12 sm:pb-0 h-full">{children}</div>
            </div>
        </AppLayout>
    );
}
