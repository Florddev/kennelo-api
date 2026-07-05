<?php

declare(strict_types=1);

namespace App\Enums;

enum AdminActionTypeEnum: string
{
    case BAN = 'ban';
    case UNBAN = 'unban';
    case FORCE_PASSWORD_RESET = 'force_password_reset';
    case VERIFY_EMAIL = 'verify_email';
    case RESEND_VERIFICATION = 'resend_verification';
    case UPDATE_STATUS = 'update_status';
    case ASSIGN_ROLES = 'assign_roles';
    case REMOVE_ROLE = 'remove_role';
    case REVIEW_IDENTITY = 'review_identity';
    case DELETE = 'delete';
    case IMPERSONATE_START = 'impersonate_start';
    case IMPERSONATE_STOP = 'impersonate_stop';
    case BULK_STATUS = 'bulk_status';
    case BULK_ROLES = 'bulk_roles';
    case EXPORT = 'export';
    case APPROVE_ACTIVITY = 'approve_activity';
    case REJECT_ACTIVITY = 'reject_activity';
    case UPDATE_ACTIVITY = 'update_activity';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
