<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\OpenApiServiceProvider;

return [
    AppServiceProvider::class,
    NotificationServiceProvider::class,
    OpenApiServiceProvider::class,
];
