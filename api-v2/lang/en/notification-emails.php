<?php

declare(strict_types=1);

return [
    'greeting' => 'Hello,',
    'reason' => 'Reason: :reason',
    'until' => 'Until :date.',

    'actions' => [
        'booking' => 'View the booking',
        'hosting_booking' => 'View the booking',
        'review' => 'Leave a review',
        'conversation' => 'Read the message',
        'organization' => 'View my business',
        'activity' => 'View the activity',
        'invitations' => 'View the invitation',
        'subscription' => 'Manage the subscription',
        'admin_booking' => 'Open in the back office',
    ],

    'booking_created' => [
        'subject' => 'New booking request',
        'line' => 'You received a new booking request for :activity.',
    ],
    'booking_confirmed' => [
        'subject' => 'Booking confirmed',
        'line' => 'Your booking for :activity is confirmed.',
    ],
    'booking_rejected' => [
        'subject' => 'Booking declined',
        'line' => 'Your booking for :activity was declined.',
    ],
    'booking_expired' => [
        'subject' => 'Booking expired',
        'line' => 'Your request for :activity has expired.',
    ],
    'booking_reminder' => [
        'subject' => 'A request is waiting for your answer',
        'line' => 'A booking request for :activity is waiting for your answer.',
    ],
    'booking_cancelled_by_client' => [
        'subject' => 'Booking cancelled by the client',
        'line' => 'The client cancelled their booking for :activity.',
    ],
    'booking_cancelled_by_pro' => [
        'subject' => 'Booking cancelled',
        'line' => 'Your booking for :activity was cancelled by the professional. You will be refunded in full.',
    ],
    'booking_cancelled_by_platform' => [
        'subject' => 'Booking cancelled by Kennelo',
        'line' => 'Kennelo cancelled your booking for :activity.',
    ],
    'team_booking_cancelled_by_platform' => [
        'subject' => 'Booking cancelled by Kennelo',
        'line' => 'Kennelo cancelled a booking for :activity.',
    ],
    'booking_completed' => [
        'subject' => 'How did your booking go?',
        'line' => 'Your booking for :activity is over. Your review helps other pet owners choose.',
    ],
    'payment_action_required' => [
        'subject' => 'Confirm your payment',
        'line' => 'An extra payment of :amount € for :activity needs your confirmation.',
    ],
    'payment_succeeded' => [
        'subject' => 'Payment confirmed',
        'line' => 'Your payment of :amount € has been confirmed.',
    ],
    'payment_failed' => [
        'subject' => 'Payment failed',
        'line' => 'Your payment of :amount € has failed.',
    ],
    'payment_refunded' => [
        'subject' => 'Refund issued',
        'line' => 'A refund of :amount € has been issued.',
    ],
    'payout_sent' => [
        'subject' => 'Payout sent',
        'line' => 'A payout of :amount € was sent for :activity.',
    ],
    'dispute_opened' => [
        'subject' => 'Payment disputed',
        'line' => 'A client disputes a payment of :amount € for :activity with their bank. Respond before :date from the Stripe dashboard.',
    ],
    'booking_disputed' => [
        'subject' => 'Payment disputed by a client',
        'line' => 'A client disputes a payment of :amount € for :activity with their bank. The payout of this booking is on hold until the decision.',
    ],
    'booking_dispute_won' => [
        'subject' => 'Dispute closed in your favour',
        'line' => 'The client\'s bank ruled in Kennelo\'s favour for :activity: the payout of the booking resumes.',
    ],
    'booking_dispute_lost' => [
        'subject' => 'Dispute lost',
        'line' => 'The client\'s bank ruled in their favour for :activity: :amount € are deducted from what you receive for this booking.',
    ],
    'new_message' => [
        'subject' => 'New message from :sender',
        'line' => ':sender wrote to you about :activity: “:preview”',
    ],
    'organization_approved' => [
        'subject' => 'Your business is approved',
        'line' => 'Kennelo approved :organization. Your activities can be booked once they are approved in turn.',
    ],
    'organization_rejected' => [
        'subject' => 'Your business was not approved',
        'line' => 'Kennelo could not approve :organization.',
    ],
    'organization_suspended' => [
        'subject' => 'Your business is suspended',
        'line' => ':organization is suspended: its activities no longer appear in search.',
    ],
    'stripe_account_activated' => [
        'subject' => 'Payments enabled',
        'line' => 'The payment account of :organization is enabled: your activities can receive bookings.',
    ],
    'activity_approved' => [
        'subject' => 'Your activity is approved',
        'line' => 'Kennelo approved :activity.',
    ],
    'activity_rejected' => [
        'subject' => 'Your activity was not approved',
        'line' => 'Kennelo could not approve :activity.',
    ],
    'activity_suspended' => [
        'subject' => 'Your activity is suspended',
        'line' => ':activity is suspended and no longer appears in search.',
    ],
    'activity_document_rejected' => [
        'subject' => 'Document declined',
        'line' => 'A document of :activity was declined. Upload a new one so that the activity stays bookable.',
    ],
    'activity_document_expiring' => [
        'subject' => 'A document expires soon',
        'line' => 'A document of :activity expires on :date. Upload a new one before then so that the activity stays bookable.',
    ],
    'activity_document_expired' => [
        'subject' => 'Document expired',
        'line' => 'A required document of :activity has expired: the activity no longer appears in search until it is replaced.',
    ],
    'member_invited' => [
        'subject' => 'Invitation to join :organization',
        'line' => 'You are invited to join the team of :organization on Kennelo.',
    ],
    'subscription_activated' => [
        'subject' => 'Subscription active',
        'line' => 'The subscription of :organization is active.',
    ],
    'subscription_payment_failed' => [
        'subject' => 'Subscription payment failed',
        'line' => 'The subscription payment of :organization failed. Update your payment method to keep your plan.',
    ],
    'subscription_downgraded' => [
        'subject' => 'Your subscription has ended',
        'line' => 'The subscription of :organization has ended: it returns to the free plan and its limits.',
    ],
    'account_banned' => [
        'subject' => 'Your account is suspended',
        'line' => 'Your Kennelo account has been suspended.',
    ],
    'account_unbanned' => [
        'subject' => 'Your account is active again',
        'line' => 'Your Kennelo account is active again.',
    ],
];
