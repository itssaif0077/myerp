<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'secret'     => Env::get('JWT_SECRET', 'antigravity_default_secret_key_32_characters!'),
    'algo'       => Env::get('JWT_ALGO', 'HS256'),
    'expiration' => (int)Env::get('JWT_EXPIRATION', 86400), // 24 hours
    'issuer'     => Env::get('JWT_ISSUER', 'antigravity_erp'),
];
