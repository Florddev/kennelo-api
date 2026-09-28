<?php

declare(strict_types=1);

return [
    'mentions' => [
        'vat_franchise' => 'ضريبة القيمة المضافة غير مطبقة، المادة 293 ب من قانون الضرائب العام الفرنسي',
    ],
    'lines' => [
        'stay' => 'إقامة (:unit) من :start إلى :end',
        'travel_fee' => 'رسوم التنقل',
        'item' => ':service',
        'item_pet' => ':service لـ :pet',
        'item_scheduled' => ':service، يوم :date الساعة :time',
        'item_pet_scheduled' => ':service لـ :pet، يوم :date الساعة :time',
        'service_fee' => 'رسوم خدمة Kennelo، حجز لدى :activity يوم :start',
        'commission' => 'عمولة Kennelo، الحجز :reference لدى :activity من :start إلى :end',
        'refund' => [
            'client_cancellation' => 'استرداد بعد إلغاء العميل (الفاتورة :number)',
            'pro_cancellation' => 'استرداد بعد إلغاء المهني (الفاتورة :number)',
            'adjustment' => 'استرداد خدمة محذوفة (الفاتورة :number)',
            'platform_cancellation' => 'استرداد بعد إلغاء Kennelo (الفاتورة :number)',
            'goodwill' => 'بادرة تجارية (الفاتورة :number)',
        ],
    ],
    'pdf' => [
        'title' => [
            'invoice' => 'فاتورة',
            'credit_note' => 'إشعار دائن',
        ],
        'number' => 'رقم :number',
        'issued_on' => 'التاريخ: :date',
        'booking' => 'الحجز :reference',
        'issuer' => 'المُصدِر',
        'recipient' => 'المستلم',
        'siren' => 'SIREN :value',
        'siret' => 'SIRET :value',
        'vat_number' => 'رقم ضريبة القيمة المضافة :value',
        'mandate' => [
            'invoice' => 'فاتورة صادرة عن :kennelo باسم :issuer ولحسابه، بموجب تفويض الفوترة المقبول بتاريخ :date.',
            'credit_note' => 'إشعار دائن صادر عن :kennelo باسم :issuer ولحسابه، بموجب تفويض الفوترة المقبول بتاريخ :date.',
        ],
        'credits' => 'إشعار دائن على الفاتورة رقم :number بتاريخ :date.',
        'period' => 'الفترة: من :start إلى :end.',
        'columns' => [
            'description' => 'البيان',
            'quantity' => 'الكمية',
            'unit_price_ttc' => 'سعر الوحدة شامل الضريبة',
            'vat_rate' => 'الضريبة',
            'total_ht' => 'المجموع دون الضريبة',
            'total_ttc' => 'المجموع شامل الضريبة',
        ],
        'totals' => [
            'ht' => 'المجموع دون الضريبة',
            'vat' => 'ضريبة القيمة المضافة',
            'ttc' => 'المجموع شامل الضريبة',
        ],
        'settlement' => [
            'paid' => 'فاتورة مسددة: دُفعت عبر الإنترنت عند الحجز.',
            'withheld' => 'مبلغ مقتطع من دفعات الفترة.',
            'refunded' => 'مبلغ مسترد إلى وسيلة الدفع الأصلية.',
        ],
    ],
];
