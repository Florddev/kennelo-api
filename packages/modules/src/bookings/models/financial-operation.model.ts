import type { FinancialOperationDto } from "./dtos/financial-operation.dto";

export class FinancialOperationModel {
    private constructor(
        public readonly id: string,
        public readonly bookingId: string | null,
        public readonly type: string,
        public readonly amount: string | null,
        public readonly currency: string | null,
        public readonly stripeReference: string | null,
        public readonly metadata: Record<string, unknown> | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: FinancialOperationDto): FinancialOperationModel {
        return new FinancialOperationModel(
            dto.id,
            dto.booking_id,
            dto.type,
            dto.amount,
            dto.currency,
            dto.stripe_reference,
            dto.metadata,
            dto.created_at,
        );
    }
}
