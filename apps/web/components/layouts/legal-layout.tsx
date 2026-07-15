import Image from "next/image";
import Link from "next/link";
import { LanguageSwitcher } from "../i18n/language-switcher";

export default function LegalLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="bg-background min-h-svh">
            <header className="w-full h-16 flex items-center border-b">
                <div className="container mx-auto max-w-3xl h-full flex justify-between items-center px-6">
                    <Link
                        href="/"
                        className="relative h-full flex justify-start items-center font-semibold text-lg"
                    >
                        <Image src="/logo_font.svg" width={120} height={26} alt="Kennelo logo" />
                    </Link>
                    <LanguageSwitcher />
                </div>
            </header>
            <main className="container mx-auto max-w-3xl px-6 py-10 md:py-16">{children}</main>
        </div>
    );
}

export function LegalArticle({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <article>
            <h1 className="text-3xl font-bold mb-8">{title}</h1>
            <div className="flex flex-col gap-6 text-sm leading-relaxed text-foreground [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:mt-4 [&_p]:text-muted-foreground [&_ul]:list-disc [&_ul]:ps-5 [&_ul]:text-muted-foreground [&_li]:mt-1">
                {children}
            </div>
        </article>
    );
}
