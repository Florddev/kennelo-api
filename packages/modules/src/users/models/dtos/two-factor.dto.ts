export type TwoFactorEnableDto = {
    qr_svg: string;
    secret: string;
};

export type RecoveryCodesDto = {
    recovery_codes: string[];
};

export type TwoFactorStepUp = { password: string } | { googleToken: string };

export function twoFactorStepUpBody(stepUp: TwoFactorStepUp): Record<string, string> {
    return "password" in stepUp
        ? { password: stepUp.password }
        : { google_token: stepUp.googleToken };
}
