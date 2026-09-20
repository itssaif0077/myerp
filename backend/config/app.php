<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'name'     => Env::get('APP_NAME', 'Antigravity ERP'),
    'env'      => Env::get('APP_ENV', 'production'),
    'debug'    => (bool)Env::get('APP_DEBUG', false),
    'url'      => Env::get('APP_URL', 'http://localhost:8000'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Kolkata'),
    'cors'     => [
        'allowed_origins' => explode(',', (string)Env::get('CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Company-Id'],
        'max_age'         => 86400,
    ]
];
