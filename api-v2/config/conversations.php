<?php

declare(strict_types=1);

return [
    // Disque privé des pièces jointes : elles ne se téléchargent que par l'API, qui vérifie l'accès à la
    // conversation. En production, un disque partagé entre les serveurs (le disque local ne l'est pas).
    'attachments_disk' => env('CONVERSATION_ATTACHMENTS_DISK', 'local'),
];
