import type { PaymentMethodDto } from "./dtos/payment-method.dto";

export class PaymentMethodModel {
    private constructor(
        public readonly id: string,
        public readonly brand: string,
        public readonly last4: string,
        public readonly expMonth: number,
        public readonly expYear: number,
        public readonly isDefault: boolean,
        public readonly cardholderName: string | null,
    ) {}

    static from(dto: PaymentMethodDto): PaymentMethodModel {
        return new PaymentMethodModel(
            dto.id,
            dto.brand,
            dto.last4,
            dto.exp_month,
            dto.exp_year,
            dto.is_default,
            dto.cardholder_name ?? null,
        );
    }
}
