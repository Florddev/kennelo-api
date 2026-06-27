"use client";

import { useState } from "react";
import Image from "next/image";
import {
    confirmTwoFactor,
    disableTwoFactor,
    enableTwoFactor,
    regenerateRecoveryCodes,
    type TwoFactorEnableDto,
} from "@workspace/modules/users";
import { Alert, AlertDescription, AlertTitle } from "@workspace/ui/components/alert";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Input } from "@workspace/ui/components/input";
import { InputOTP, InputOTPGroup, InputOTPSlot } from "@workspace/ui/components/input-otp";
import { cn } from "@workspace/ui/lib/utils";
import { ShieldCheck } from "@solar-icons/react";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth";

type DialogKind = "enable" | "disable" | "regenerate";

type DialogCopy = { title: string; warning: string; confirm: string };

function RecoveryCodes({ codes, onDone }: { codes: string[]; onDone: () => void }) {
    const t = useTranslations();

    const handleCopy = () => {
        navigator.clipboard.writeText(codes.join("\n")).catch(() => undefined);
    };

    const handleDownload = () => {
        const blob = new Blob([codes.join("\n")], { type: "text/plain" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = "kennelo-recovery-codes.txt";
        link.click();
        URL.revokeObjectURL(url);
    };

    return (
        <Card className="ring-0">
            <CardContent className="flex flex-col gap-4 p-6">
                <Alert variant="destructive">
                    <AlertTitle>{t("features.auth.twoFactor.setup.recoveryTitle")}</AlertTitle>
                    <AlertDescription>
                        {t("features.auth.twoFactor.setup.recoveryWarning")}
                    </AlertDescription>
                </Alert>
                <div className="grid grid-cols-2 gap-2 rounded-2xl bg-muted p-4 font-mono text-sm">
                    {codes.map((code) => (
                        <span key={code} className="text-center">
                            {code}
                        </span>
                    ))}
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" onClick={handleCopy}>
                        {t("features.auth.twoFactor.setup.copy")}
                    </Button>
                    <Button type="button" variant="outline" onClick={handleDownload}>
                        {t("features.auth.twoFactor.setup.download")}
                    </Button>
                    <Button type="button" onClick={onDone}>
                        {t("features.auth.twoFactor.setup.done")}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function SetupCard({
    setup,
    code,
    setCode,
    isLoading,
    onCancel,
    onConfirm,
}: {
    setup: TwoFactorEnableDto;
    code: string;
    setCode: (value: string) => void;
    isLoading: boolean;
    onCancel: () => void;
    onConfirm: () => void;
}) {
    const t = useTranslations();

    return (
        <Card className="ring-0">
            <CardContent className="flex flex-col items-center gap-4 p-6 text-center">
                <p className="text-sm text-muted-foreground">
                    {t("features.auth.twoFactor.setup.scanInstruction")}
                </p>
                <Image src={setup.qr_svg} alt="" width={200} height={200} unoptimized />
                <p className="text-xs text-muted-foreground">
                    {t("features.auth.twoFactor.setup.manualSecret")}
                </p>
                <code className="rounded-2xl bg-muted px-3 py-2 font-mono text-sm tracking-widest">
                    {setup.secret}
                </code>
                <p className="text-sm font-medium">
                    {t("features.auth.twoFactor.setup.enterCode")}
                </p>
                <InputOTP maxLength={6} value={code} onChange={setCode} disabled={isLoading}>
                    <InputOTPGroup>
                        {[0, 1, 2, 3, 4, 5].map((index) => (
                            <InputOTPSlot key={index} index={index} className="size-11" />
                        ))}
                    </InputOTPGroup>
                </InputOTP>
                <div className="flex gap-2">
                    <Button type="button" variant="outline" onClick={onCancel} disabled={isLoading}>
                        {t("common.actions.cancel")}
                    </Button>
                    <Button
                        type="button"
                        onClick={onConfirm}
                        disabled={isLoading || code.length !== 6}
                    >
                        {t("features.auth.twoFactor.setup.confirm")}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function PasswordDialog({
    kind,
    copy,
    password,
    setPassword,
    isLoading,
    onClose,
    onConfirm,
}: {
    kind: DialogKind | null;
    copy: DialogCopy | null;
    password: string;
    setPassword: (value: string) => void;
    isLoading: boolean;
    onClose: () => void;
    onConfirm: () => void;
}) {
    const t = useTranslations();

    return (
        <Dialog
            open={kind !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent className="max-w-md p-6">
                <DialogHeader>
                    <DialogTitle>{copy?.title}</DialogTitle>
                    <DialogDescription>{copy?.warning}</DialogDescription>
                </DialogHeader>
                <Input
                    type="password"
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    placeholder={t("common.placeholders.password")}
                    autoComplete="current-password"
                    disabled={isLoading}
                />
                <Button
                    type="button"
                    variant={kind === "disable" ? "destructive" : "default"}
                    onClick={onConfirm}
                    disabled={isLoading || password.length === 0}
                >
                    {copy?.confirm}
                </Button>
            </DialogContent>
        </Dialog>
    );
}

export function TwoFactorSection() {
    const { user, refreshUser } = useAuth();
    const t = useTranslations();
    const { isLoading, execute } = useAsyncState();
    const [setup, setSetup] = useState<TwoFactorEnableDto | null>(null);
    const [code, setCode] = useState("");
    const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null);
    const [dialog, setDialog] = useState<DialogKind | null>(null);
    const [password, setPassword] = useState("");

    const enabled = user?.twoFactorEnabled ?? false;
    const recoveryCount = user?.twoFactorRecoveryCodesCount ?? 0;
    const lowRecoveryCodes = recoveryCount <= 2;

    const closeDialog = () => {
        setDialog(null);
        setPassword("");
    };

    const handleConfirm = async () => {
        const codes = await execute(() => confirmTwoFactor({ code }));
        if (codes) {
            setSetup(null);
            setCode("");
            setRecoveryCodes(codes);
            await refreshUser();
        }
    };

    const handlePasswordConfirm = async () => {
        if (dialog === "enable") {
            const result = await execute(() => enableTwoFactor(password));
            if (result) {
                closeDialog();
                setSetup(result);
            }
            return;
        }

        if (dialog === "disable") {
            const ok = await execute(() => disableTwoFactor({ password }).then(() => true));
            if (ok) {
                closeDialog();
                await refreshUser();
            }
            return;
        }

        const codes = await execute(() => regenerateRecoveryCodes({ password }));
        if (codes) {
            closeDialog();
            setRecoveryCodes(codes);
            await refreshUser();
        }
    };

    const dialogCopy: Record<DialogKind, DialogCopy> = {
        enable: {
            title: t("features.auth.twoFactor.section.enableTitle"),
            warning: t("features.auth.twoFactor.section.enableWarning"),
            confirm: t("features.auth.twoFactor.section.enable"),
        },
        disable: {
            title: t("features.auth.twoFactor.disable.title"),
            warning: t("features.auth.twoFactor.disable.warning"),
            confirm: t("features.auth.twoFactor.disable.confirm"),
        },
        regenerate: {
            title: t("features.auth.twoFactor.regenerate.title"),
            warning: t("features.auth.twoFactor.regenerate.warning"),
            confirm: t("features.auth.twoFactor.regenerate.confirm"),
        },
    };

    if (recoveryCodes) {
        return <RecoveryCodes codes={recoveryCodes} onDone={() => setRecoveryCodes(null)} />;
    }

    if (setup) {
        return (
            <SetupCard
                setup={setup}
                code={code}
                setCode={setCode}
                isLoading={isLoading}
                onCancel={() => {
                    setSetup(null);
                    setCode("");
                }}
                onConfirm={handleConfirm}
            />
        );
    }

    return (
        <Card className="ring-0">
            <CardContent className="flex flex-col gap-4 p-6">
                <div className="flex items-center gap-3">
                    <ShieldCheck className="size-6 text-primary" />
                    <div className="flex flex-col">
                        <span className="font-medium">
                            {t("features.auth.twoFactor.section.title")}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            {enabled
                                ? t("features.auth.twoFactor.status.enabled")
                                : t("features.auth.twoFactor.status.disabled")}
                        </span>
                    </div>
                </div>

                {enabled ? (
                    <>
                        <p
                            className={cn(
                                "text-sm",
                                lowRecoveryCodes ? "text-destructive" : "text-muted-foreground",
                            )}
                        >
                            {t("features.auth.twoFactor.recoveryCount", { count: recoveryCount })}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialog("regenerate")}
                            >
                                {t("features.auth.twoFactor.regenerate.action")}
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => setDialog("disable")}
                            >
                                {t("features.auth.twoFactor.disable.action")}
                            </Button>
                        </div>
                    </>
                ) : (
                    <Button
                        type="button"
                        className="w-fit"
                        onClick={() => setDialog("enable")}
                        disabled={isLoading}
                    >
                        {t("features.auth.twoFactor.section.enable")}
                    </Button>
                )}

                <PasswordDialog
                    kind={dialog}
                    copy={dialog ? dialogCopy[dialog] : null}
                    password={password}
                    setPassword={setPassword}
                    isLoading={isLoading}
                    onClose={closeDialog}
                    onConfirm={handlePasswordConfirm}
                />
            </CardContent>
        </Card>
    );
}
