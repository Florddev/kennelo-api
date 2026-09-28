<?php

declare(strict_types=1);

return [
    'greeting' => 'مرحبًا،',
    'reason' => 'السبب: :reason',
    'until' => 'حتى :date.',

    'actions' => [
        'booking' => 'عرض الحجز',
        'hosting_booking' => 'عرض الحجز',
        'review' => 'اترك تقييمًا',
        'conversation' => 'قراءة الرسالة',
        'organization' => 'عرض شركتي',
        'activity' => 'عرض النشاط',
        'invitations' => 'عرض الدعوة',
        'subscription' => 'إدارة الاشتراك',
        'admin_booking' => 'فتح في لوحة الإدارة',
    ],

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
    'booking_cancelled_by_platform' => [
        'subject' => 'ألغت Kennelo الحجز',
        'line' => 'ألغت Kennelo حجزك لـ :activity.',
    ],
    'team_booking_cancelled_by_platform' => [
        'subject' => 'ألغت Kennelo الحجز',
        'line' => 'ألغت Kennelo حجزًا لـ :activity.',
    ],
    'booking_completed' => [
        'subject' => 'كيف كان حجزك؟',
        'line' => 'انتهى حجزك لـ :activity. يساعد تقييمك أصحاب الحيوانات الآخرين على الاختيار.',
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
    'dispute_opened' => [
        'subject' => 'دفعة متنازع عليها',
        'line' => 'يعترض عميل لدى بنكه على دفعة بقيمة :amount € لـ :activity. قدّم الرد قبل :date من لوحة تحكم Stripe.',
    ],
    'booking_disputed' => [
        'subject' => 'اعتراض عميل على دفعة',
        'line' => 'يعترض عميل لدى بنكه على دفعة بقيمة :amount € لـ :activity. تم تعليق تحويل هذا الحجز حتى صدور القرار.',
    ],
    'booking_dispute_won' => [
        'subject' => 'أُغلق الاعتراض لصالحك',
        'line' => 'حكم بنك العميل لصالح Kennelo بخصوص :activity: يُستأنف تحويل الحجز.',
    ],
    'booking_dispute_lost' => [
        'subject' => 'خسارة الاعتراض',
        'line' => 'حكم بنك العميل لصالحه بخصوص :activity: سيتم خصم :amount € مما يعود إليك عن هذا الحجز.',
    ],
    'new_message' => [
        'subject' => 'رسالة جديدة من :sender',
        'line' => 'كتب إليك :sender بخصوص :activity: «:preview»',
    ],
    'organization_approved' => [
        'subject' => 'تم اعتماد شركتك',
        'line' => 'اعتمدت Kennelo :organization. يمكن حجز أنشطتك بعد اعتمادها بدورها.',
    ],
    'organization_rejected' => [
        'subject' => 'لم يتم اعتماد شركتك',
        'line' => 'لم تتمكن Kennelo من اعتماد :organization.',
    ],
    'organization_suspended' => [
        'subject' => 'تم تعليق شركتك',
        'line' => 'تم تعليق :organization: لم تعد أنشطتها تظهر في البحث.',
    ],
    'stripe_account_activated' => [
        'subject' => 'تم تفعيل المدفوعات',
        'line' => 'تم تفعيل حساب الدفع لـ :organization: يمكن لأنشطتك استقبال الحجوزات.',
    ],
    'activity_approved' => [
        'subject' => 'تم اعتماد نشاطك',
        'line' => 'اعتمدت Kennelo :activity.',
    ],
    'activity_rejected' => [
        'subject' => 'لم يتم اعتماد نشاطك',
        'line' => 'لم تتمكن Kennelo من اعتماد :activity.',
    ],
    'activity_suspended' => [
        'subject' => 'تم تعليق نشاطك',
        'line' => 'تم تعليق :activity ولم يعد يظهر في البحث.',
    ],
    'activity_document_rejected' => [
        'subject' => 'تم رفض المستند',
        'line' => 'تم رفض مستند لـ :activity. ارفع مستندًا جديدًا ليبقى النشاط قابلًا للحجز.',
    ],
    'activity_document_expiring' => [
        'subject' => 'مستند تنتهي صلاحيته قريبًا',
        'line' => 'تنتهي صلاحية مستند لـ :activity في :date. ارفع مستندًا جديدًا قبل هذا التاريخ ليبقى النشاط قابلًا للحجز.',
    ],
    'activity_document_expired' => [
        'subject' => 'انتهت صلاحية المستند',
        'line' => 'انتهت صلاحية مستند إلزامي لـ :activity: لم يعد النشاط يظهر في البحث حتى يتم استبداله.',
    ],
    'member_invited' => [
        'subject' => 'دعوة للانضمام إلى :organization',
        'line' => 'أنت مدعو للانضمام إلى فريق :organization على Kennelo.',
    ],
    'subscription_activated' => [
        'subject' => 'تم تفعيل الاشتراك',
        'line' => 'اشتراك :organization نشط.',
    ],
    'subscription_payment_failed' => [
        'subject' => 'فشل دفع الاشتراك',
        'line' => 'فشل دفع اشتراك :organization. حدّث وسيلة الدفع للاحتفاظ بباقتك.',
    ],
    'subscription_downgraded' => [
        'subject' => 'انتهى اشتراكك',
        'line' => 'انتهى اشتراك :organization: تعود إلى الباقة المجانية وحدودها.',
    ],
    'account_banned' => [
        'subject' => 'تم تعليق حسابك',
        'line' => 'تم تعليق حسابك على Kennelo.',
    ],
    'account_unbanned' => [
        'subject' => 'تمت إعادة تفعيل حسابك',
        'line' => 'حسابك على Kennelo نشط من جديد.',
    ],
];
