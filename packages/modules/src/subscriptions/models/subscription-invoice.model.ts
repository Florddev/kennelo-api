import type { SubscriptionInvoiceDto } from "./dtos/subscription-invoice.dto";

export class SubscriptionInvoiceModel {
    private constructor(
        public readonly id: string,
        public readonly number: string | null,
        public readonly status: string | null,
        public readonly amountPaid: string,
        public readonly amountDue: string,
        public readonly currency: string,
        public readonly created: string | null,
        public readonly invoicePdf: string | null,
        public readonly hostedInvoiceUrl: string | null,
    ) {}

    static from(dto: SubscriptionInvoiceDto): SubscriptionInvoiceModel {
        return new SubscriptionInvoiceModel(
            dto.id,
            dto.number ?? null,
            dto.status ?? null,
            dto.amount_paid,
            dto.amount_due,
            dto.currency,
            dto.created ?? null,
            dto.invoice_pdf ?? null,
            dto.hosted_invoice_url ?? null,
        );
    }

    isPaid(): boolean {
        return this.status === "paid";
    }
}
