import { useTranslations } from "next-intl";

export function TypingIndicator({ name }: { name: string }) {
    const t = useTranslations();
    return (
        <div className="mt-3 flex flex-col items-start">
            <p className="mb-1 ms-1 text-[10px] text-muted-foreground">
                {t("features.conversations.typing", { name })}
            </p>
            <div className="flex items-center gap-1 rounded-2xl bg-muted px-3 py-3">
                <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:0ms]" />
                <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:150ms]" />
                <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:300ms]" />
            </div>
        </div>
    );
}
