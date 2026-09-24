<?php

declare(strict_types=1);

return [
    'magic_link' => [
        'subject' => 'Votre lien de connexion à Kennelo',
        'intro' => 'Cliquez sur le bouton ci-dessous pour vous connecter à Kennelo.',
        'action' => 'Se connecter à Kennelo',
        'validity' => 'Ce lien est valable :minutes minutes et ne peut être utilisé qu\'une seule fois.',
        'ignore' => 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail.',
    ],
    'two_factor_enabled' => [
        'subject' => 'Double authentification activée',
        'line' => 'La double authentification vient d\'être activée sur votre compte Kennelo.',
    ],
    'two_factor_disabled' => [
        'subject' => 'Double authentification désactivée',
        'line' => 'La double authentification vient d\'être désactivée sur votre compte Kennelo.',
    ],
    'security_notice' => 'Si vous n\'êtes pas à l\'origine de cette action, réinitialisez votre mot de passe et contactez immédiatement le support.',
];
