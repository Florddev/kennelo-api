import { useTranslations } from "next-intl";
import { useConversations } from "../hooks/use-conversations";
import { useRef, useState } from "react";
import { cn } from "@workspace/ui/lib/utils";
import { FilePreviewChip } from "./file-preview-chip";
import { Button } from "@workspace/ui/components/button";
import { Plus } from "lucide-react";
import { ResizableTextarea } from "@workspace/ui/components/resizable-textarea";
import { ArrowUp } from "@solar-icons/react";

export function MessageComposer() {
    const t = useTranslations();
    const { sendMessage, notifyTyping } = useConversations();
    const [inputValue, setInputValue] = useState("");
    const [attachedFiles, setAttachedFiles] = useState<File[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleSend = async () => {
        const content = inputValue.trim();
        if (!content && attachedFiles.length === 0) return;
        const filesToSend = [...attachedFiles];
        setInputValue("");
        setAttachedFiles([]);
        if (fileInputRef.current) fileInputRef.current.value = "";
        await sendMessage(content, filesToSend.length > 0 ? filesToSend : undefined);
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newFiles = Array.from(e.target.files ?? []);
        if (fileInputRef.current) fileInputRef.current.value = "";
        setAttachedFiles((prev) => [...prev, ...newFiles]);
    };

    const canSend = !!inputValue.trim() || attachedFiles.length > 0;
    const handleRemoveFile = (index: number) =>
        setAttachedFiles((prev) => prev.filter((_, j) => j !== index));

    return (
        <div
            className={cn(
                "flex flex-shrink-0 flex-col border-t bg-card rounded-t-3xl md:border shadow-sm",
                attachedFiles.length > 0 && "pt-3",
            )}
        >
            {attachedFiles.length > 0 && (
                <div className="flex gap-2 overflow-x-auto px-4">
                    {attachedFiles.map((file, i) => (
                        <FilePreviewChip key={i} file={file} onRemove={() => handleRemoveFile(i)} />
                    ))}
                </div>
            )}
            <div className="flex items-start gap-1 px-4 py-3 md:p-5 md:gap-2">
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    onChange={handleFileSelect}
                    className="hidden"
                />
                <Button
                    variant="flat"
                    size="icon-xs"
                    onClick={() => fileInputRef.current?.click()}
                    aria-label={t("features.conversations.attachFile")}
                    className="mb-0.5 flex-shrink-0"
                >
                    <Plus className="size-3.5" />
                </Button>

                <ResizableTextarea
                    value={inputValue}
                    onChange={(e) => {
                        setInputValue(e.target.value);
                        notifyTyping();
                    }}
                    onKeyDown={handleKeyDown}
                    placeholder={t("features.conversations.messagePlaceholder")}
                    className="h-fit max-h-16 min-h-0 flex-1 rounded-xl py-0.5 px-1.5 text-sm border-none bg-transparent focus-visible:ring-[0]"
                    rows={1}
                />
                <Button
                    size="icon-xs"
                    variant={canSend ? "default" : "flat"}
                    onClick={handleSend}
                    disabled={!canSend}
                    className="mb-0.5 flex-shrink-0"
                >
                    <ArrowUp className="size-3.5" />
                </Button>
            </div>
        </div>
    );
}
