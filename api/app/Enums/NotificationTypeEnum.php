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
    case BOOKING_COMPLETED = 'booking_completed';

    case PAYMENT_SUCCEEDED = 'payment_succeeded';
    case PAYMENT_FAILED = 'payment_failed';
    case PAYMENT_PROCESSING = 'payment_processing';
    case PAYMENT_REFUNDED = 'payment_refunded';
    case STRIPE_ACCOUNT_ACTIVATED = 'stripe_account_activated';

    case NEW_MESSAGE = 'new_message';

    case REVIEW_RECEIVED = 'review_received';
    case REVIEW_PUBLISHED = 'review_published';
    case REVIEW_RESPONSE = 'review_response';
    case REVIEW_REPORTED = 'review_reported';
    case REVIEW_REPORT_RESOLVED = 'review_report_resolved';

    case IDENTITY_SUBMITTED = 'identity_submitted';
    case IDENTITY_APPROVED = 'identity_approved';
    case IDENTITY_REJECTED = 'identity_rejected';
    case ACCOUNT_STATUS_CHANGED = 'account_status_changed';

    case COLLABORATOR_INVITED = 'collaborator_invited';
    case COLLABORATOR_ACCEPTED = 'collaborator_accepted';
    case COLLABORATOR_DECLINED = 'collaborator_declined';

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
}
