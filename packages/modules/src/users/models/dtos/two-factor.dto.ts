export type TwoFactorEnableDto = {
    qr_svg: string;
    secret: string;
};

export type RecoveryCodesDto = {
    recovery_codes: string[];
};
