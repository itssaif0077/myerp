<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use Closure;

/**
 * Enterprise CORS (Cross-Origin Resource Sharing) Middleware
 * Allows seamless, secure cross-origin communication between the UI/POS and the API.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Company-Id');

        // Handle preflight OPTIONS request immediately
        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        return $next($request);
    }
}
