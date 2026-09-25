<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case BOOKING_CREATED = 'booking_created';
    case BOOKING_CONFIRMED = 'booking_confirmed';
    case BOOKING_CANCELLED_BY_CLIENT = 'booking_cancelled_by_client';
    case BOOKING_REJECTED = 'booking_rejected';
    case BOOKING_EXPIRED = 'booking_expired';
    case BOOKING_REMINDER = 'booking_reminder';
    case BOOKING_COMPLETED = 'booking_completed';

    case PAYMENT_SUCCEEDED = 'payment_succeeded';
    case PAYMENT_FAILED = 'payment_failed';
    case PAYMENT_PROCESSING = 'payment_processing';
    case PAYMENT_REFUNDED = 'payment_refunded';
    case PAYOUT_SENT = 'payout_sent';
    case STRIPE_ACCOUNT_ACTIVATED = 'stripe_account_activated';

    case NEW_MESSAGE = 'new_message';

    case REVIEW_RECEIVED = 'review_received';
    case REVIEW_PUBLISHED = 'review_published';
    case REVIEW_RESPONSE = 'review_response';
    case REVIEW_REPORTED = 'review_reported';
    case REVIEW_REPORT_RESOLVED = 'review_report_resolved';

    case ACCOUNT_STATUS_CHANGED = 'account_status_changed';
    case ACCOUNT_BANNED = 'account_banned';
    case ACCOUNT_UNBANNED = 'account_unbanned';

    case MEMBER_INVITED = 'member_invited';
    case MEMBER_ACCEPTED = 'member_accepted';
    case MEMBER_DECLINED = 'member_declined';

    case ORGANIZATION_APPROVED = 'organization_approved';
    case ORGANIZATION_REJECTED = 'organization_rejected';
    case ORGANIZATION_SUSPENDED = 'organization_suspended';

    case ACTIVITY_APPROVED = 'activity_approved';
    case ACTIVITY_REJECTED = 'activity_rejected';
    case ACTIVITY_SUSPENDED = 'activity_suspended';
    case ACTIVITY_DOCUMENT_APPROVED = 'activity_document_approved';
    case ACTIVITY_DOCUMENT_REJECTED = 'activity_document_rejected';
    case ACTIVITY_DOCUMENT_EXPIRING = 'activity_document_expiring';
    case ACTIVITY_DOCUMENT_EXPIRED = 'activity_document_expired';

    case SUBSCRIPTION_ACTIVATED = 'subscription_activated';
    case SUBSCRIPTION_PAYMENT_FAILED = 'subscription_payment_failed';
    case SUBSCRIPTION_DOWNGRADED = 'subscription_downgraded';

    case FAVORITE_ADDED = 'favorite_added';
    case ACTIVITY_CREATED = 'activity_created';
    case ACTIVITY_UPDATED = 'activity_updated';
    case ACTIVITY_DELETED = 'activity_deleted';
    case PET_CREATED = 'pet_created';
    case PET_UPDATED = 'pet_updated';
    case PET_DELETED = 'pet_deleted';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isTierThree(): bool
    {
        return in_array($this, [
            self::FAVORITE_ADDED,
            self::ACTIVITY_CREATED,
            self::ACTIVITY_UPDATED,
            self::ACTIVITY_DELETED,
            self::PET_CREATED,
            self::PET_UPDATED,
            self::PET_DELETED,
        ], true);
    }

    public function sendsEmail(): bool
    {
        return in_array($this, [
            self::BOOKING_CREATED,
            self::BOOKING_CONFIRMED,
            self::BOOKING_REJECTED,
            self::BOOKING_EXPIRED,
            self::BOOKING_REMINDER,
            self::PAYMENT_SUCCEEDED,
            self::PAYMENT_FAILED,
            self::PAYMENT_REFUNDED,
            self::PAYOUT_SENT,
        ], true);
    }
}
