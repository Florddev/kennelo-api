import { useLayoutEffect, useRef } from "react";
import { Textarea } from "./textarea";

function ResizableTextarea({ ...props }: React.ComponentProps<"textarea">) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const resizeTextarea = () => {
        const element = textareaRef.current;
        if (!element) return;

        element.style.height = "auto";

        const maxHeight = Number.parseFloat(window.getComputedStyle(element).maxHeight);
        const nextHeight = element.scrollHeight;

        if (!Number.isNaN(maxHeight) && maxHeight > 0) {
            element.style.height = `${Math.min(nextHeight, maxHeight)}px`;
            element.style.overflowY = nextHeight > maxHeight ? "auto" : "hidden";
            return;
        }

        element.style.height = `${nextHeight}px`;
    };

    useLayoutEffect(() => {
        resizeTextarea();
    }, [props.value]);

    return <Textarea ref={textareaRef} {...props} />;
}

export { ResizableTextarea };
