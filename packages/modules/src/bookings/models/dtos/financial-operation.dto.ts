export type FinancialOperationDto = {
    id: string;
    booking_id: string | null;
    type: string;
    amount: string | null;
    currency: string | null;
    stripe_reference: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
};
