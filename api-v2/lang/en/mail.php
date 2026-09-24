<?php

declare(strict_types=1);

return [
    'magic_link' => [
        'subject' => 'Your Kennelo sign-in link',
        'intro' => 'Click the button below to sign in to Kennelo.',
        'action' => 'Sign in to Kennelo',
        'validity' => 'This link is valid for :minutes minutes and can only be used once.',
        'ignore' => 'If you did not request this link, you can ignore this email.',
    ],
    'two_factor_enabled' => [
        'subject' => 'Two-factor authentication enabled',
        'line' => 'Two-factor authentication has just been enabled on your Kennelo account.',
    ],
    'two_factor_disabled' => [
        'subject' => 'Two-factor authentication disabled',
        'line' => 'Two-factor authentication has just been disabled on your Kennelo account.',
    ],
    'security_notice' => 'If you did not perform this action, reset your password and contact support immediately.',
];
