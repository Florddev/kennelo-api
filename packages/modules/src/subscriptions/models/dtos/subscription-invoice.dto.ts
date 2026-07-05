export type SubscriptionInvoiceDto = {
    id: string;
    number: string | null;
    status: string | null;
    amount_paid: string;
    amount_due: string;
    currency: string;
    created: string | null;
    invoice_pdf: string | null;
    hosted_invoice_url: string | null;
};
