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
import { ShieldCheck } from "@solar-icons/react";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth";

function RecoveryCodes({ codes, onDone }: { codes: string[]; onDone: () => void }) {
    const t = useTranslations();

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
                <Button type="button" className="w-fit" onClick={onDone}>
                    {t("features.auth.twoFactor.setup.done")}
                </Button>
            </CardContent>
        </Card>
    );
}

export function TwoFactorSection() {
    const { user, refreshUser } = useAuth();
    const t = useTranslations();
    const { isLoading, execute } = useAsyncState();
    const [setup, setSetup] = useState<TwoFactorEnableDto | null>(null);
    const [code, setCode] = useState("");
    const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null);
    const [dialog, setDialog] = useState<"disable" | "regenerate" | null>(null);
    const [password, setPassword] = useState("");

    const enabled = user?.twoFactorEnabled ?? false;

    const handleEnable = async () => {
        const result = await execute(() => enableTwoFactor());
        if (result) {
            setSetup(result);
        }
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
        if (dialog === "disable") {
            const ok = await execute(() => disableTwoFactor({ password }).then(() => true));
            if (ok) {
                setDialog(null);
                setPassword("");
                await refreshUser();
            }
            return;
        }

        const codes = await execute(() => regenerateRecoveryCodes({ password }));
        if (codes) {
            setDialog(null);
            setPassword("");
            setRecoveryCodes(codes);
        }
    };

    if (recoveryCodes) {
        return <RecoveryCodes codes={recoveryCodes} onDone={() => setRecoveryCodes(null)} />;
    }

    if (setup) {
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
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setSetup(null);
                                setCode("");
                            }}
                            disabled={isLoading}
                        >
                            {t("common.actions.cancel")}
                        </Button>
                        <Button
                            type="button"
                            onClick={handleConfirm}
                            disabled={isLoading || code.length !== 6}
                        >
                            {t("features.auth.twoFactor.setup.confirm")}
                        </Button>
                    </div>
                </CardContent>
            </Card>
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
                ) : (
                    <Button
                        type="button"
                        className="w-fit"
                        onClick={handleEnable}
                        disabled={isLoading}
                    >
                        {t("features.auth.twoFactor.section.enable")}
                    </Button>
                )}

                <Dialog
                    open={dialog !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setDialog(null);
                            setPassword("");
                        }
                    }}
                >
                    <DialogContent className="max-w-md p-6">
                        <DialogHeader>
                            <DialogTitle>
                                {dialog === "disable"
                                    ? t("features.auth.twoFactor.disable.title")
                                    : t("features.auth.twoFactor.regenerate.title")}
                            </DialogTitle>
                            <DialogDescription>
                                {dialog === "disable"
                                    ? t("features.auth.twoFactor.disable.warning")
                                    : t("features.auth.twoFactor.regenerate.warning")}
                            </DialogDescription>
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
                            variant={dialog === "disable" ? "destructive" : "default"}
                            onClick={handlePasswordConfirm}
                            disabled={isLoading || password.length === 0}
                        >
                            {dialog === "disable"
                                ? t("features.auth.twoFactor.disable.confirm")
                                : t("features.auth.twoFactor.regenerate.confirm")}
                        </Button>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    );
}
