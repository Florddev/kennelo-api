import ActivityLayout from "./activities-layout";

export type Query = {
    id: string;
};

export function generateStaticParams(): Query[] {
    return [{ id: "[id]" }];
}

export default function Layout({ children }: { children: React.ReactNode }) {
    return <ActivityLayout>{children}</ActivityLayout>;
}
