"use client";
import { useState, useCallback } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";

export type AsyncState<T> = {
    data: T | undefined;
    isLoading: boolean;
    error: string | undefined;
};

export type AsyncStateOptions<T> = {
    setter?: (data: T) => void;
    onSuccess?: (data: T) => void;
    onFailure?: (error: string) => void;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setFieldError?: (field: any, error: { message: string }) => void;
    displayError?: boolean;
    defaultError?: string;
};

type ApiErrorLike = Error & {
    status?: number;
    fieldErrors?: Record<string, string[]>;
};

const HTTP_TRANSLATED_CODES = [
    "400",
    "401",
    "403",
    "404",
    "408",
    "409",
    "422",
    "429",
    "500",
    "502",
    "503",
    "504",
] as const;

function resolveFieldErrors(err: unknown): Record<string, string[]> | undefined {
    if (err && typeof err === "object" && "fieldErrors" in err) {
        const fieldErrors = (err as { fieldErrors?: unknown }).fieldErrors;
        if (fieldErrors && typeof fieldErrors === "object") {
            return fieldErrors as Record<string, string[]>;
        }
    }
    return undefined;
}

function handleAsyncSuccess<T>(result: T, options?: AsyncStateOptions<T>): void {
    if (result !== undefined) options?.setter?.(result);
    options?.onSuccess?.(result);
}

function getApiErrorInfo(err: unknown): {
    status: number | undefined;
    apiMessage: string | undefined;
} {
    const apiError = err as ApiErrorLike;
    const status = apiError?.status;
    const hasMessage =
        err instanceof Error && err.message && !err.message.startsWith("HTTP Error ");
    return { status, apiMessage: hasMessage ? err.message : undefined };
}

function isTranslatableStatus(status: number): boolean {
    return HTTP_TRANSLATED_CODES.includes(String(status) as (typeof HTTP_TRANSLATED_CODES)[number]);
}

function applyFieldErrors<T>(
    fieldErrors: Record<string, string[]>,
    options: AsyncStateOptions<T>,
): void {
    Object.entries(fieldErrors).forEach(([field, messages]) => {
        options.setFieldError!(field, { message: messages[0] ?? "" });
    });
}

function showErrorToast(message: string): void {
    toast.error(message, {
        position: "bottom-right",
        classNames: {
            icon: "text-destructive",
            content: "text-destructive",
            toast: "border !border-destructive/30 !bg-destructive/10 backdrop-blur-sm",
        },
    });
}

export function useAsyncState() {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | undefined>();
    const t = useTranslations();

    const resolveErrorMessage = useCallback(
        (err: unknown, defaultError?: string): string => {
            const { status, apiMessage } = getApiErrorInfo(err);

            if (status && status >= 400 && status < 500 && apiMessage) {
                return apiMessage;
            }

            if (status && isTranslatableStatus(status)) {
                return t(`errors.http.${status}`);
            }

            return apiMessage ?? defaultError ?? t("errors.http.500");
        },
        [t],
    );

    const handleError = useCallback(
        <T>(
            err: unknown,
            options: AsyncStateOptions<T> | undefined,
            setStateError: (message: string) => void,
        ): void => {
            const message = resolveErrorMessage(err, options?.defaultError);
            const fieldErrors = resolveFieldErrors(err);
            const hasFieldErrors =
                fieldErrors && options?.setFieldError && Object.keys(fieldErrors).length > 0;

            if (hasFieldErrors) {
                applyFieldErrors(fieldErrors, options!);
            } else {
                setStateError(message);
            }

            options?.onFailure?.(message);

            if (options?.displayError !== false) {
                showErrorToast(message);
            }
        },
        [resolveErrorMessage],
    );

    const execute = useCallback(
        async <T>(
            callback: () => Promise<T>,
            options?: AsyncStateOptions<T>,
        ): Promise<T | undefined> => {
            setIsLoading(true);
            setError(undefined);

            if (options && options?.displayError === undefined) options.displayError = false;

            try {
                const result = await callback();
                handleAsyncSuccess(result, options);
                return result;
            } catch (err) {
                handleError(err, options, setError);
                return undefined;
            } finally {
                setIsLoading(false);
            }
        },
        [handleError],
    );

    const reset = useCallback(() => {
        setIsLoading(false);
        setError(undefined);
    }, []);

    return { execute, isLoading, error, reset };
}
