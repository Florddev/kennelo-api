export type PaymentMethodDto = {
    id: string;
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
    is_default: boolean;
    cardholder_name: string | null;
};
