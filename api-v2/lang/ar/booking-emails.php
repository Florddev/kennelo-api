<?php

declare(strict_types=1);

return [
    'greeting' => 'مرحبًا،',
    'action' => 'عرض الحجز',

    'booking_created' => [
        'subject' => 'طلب حجز جديد',
        'line' => 'لقد تلقيت طلب حجز جديدًا لـ :activity.',
    ],
    'booking_confirmed' => [
        'subject' => 'تم تأكيد الحجز',
        'line' => 'تم تأكيد حجزك لـ :activity.',
    ],
    'booking_rejected' => [
        'subject' => 'تم رفض الحجز',
        'line' => 'تم رفض حجزك لـ :activity.',
    ],
    'booking_expired' => [
        'subject' => 'انتهت صلاحية الحجز',
        'line' => 'انتهت صلاحية طلبك لـ :activity.',
    ],
    'booking_reminder' => [
        'subject' => 'طلب بانتظار ردك',
        'line' => 'طلب حجز لـ :activity بانتظار ردك.',
    ],
    'booking_cancelled_by_client' => [
        'subject' => 'ألغى العميل الحجز',
        'line' => 'ألغى العميل حجزه لـ :activity.',
    ],
    'booking_cancelled_by_pro' => [
        'subject' => 'تم إلغاء الحجز',
        'line' => 'ألغى المحترف حجزك لـ :activity. سيتم رد المبلغ كاملًا.',
    ],
    'payment_action_required' => [
        'subject' => 'أكّد دفعتك',
        'line' => 'دفعة إضافية بقيمة :amount € لـ :activity بانتظار تأكيدك.',
    ],
    'payment_succeeded' => [
        'subject' => 'تم تأكيد الدفع',
        'line' => 'تم تأكيد دفعتك بقيمة :amount €.',
    ],
    'payment_failed' => [
        'subject' => 'فشل الدفع',
        'line' => 'فشلت دفعتك بقيمة :amount €.',
    ],
    'payment_refunded' => [
        'subject' => 'تم رد المبلغ',
        'line' => 'تم رد مبلغ :amount €.',
    ],
    'payout_sent' => [
        'subject' => 'تم إرسال التحويل',
        'line' => 'تم إرسال تحويل بقيمة :amount € عن :activity.',
    ],
];
