<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\JwtService;
use Closure;

/**
 * Enterprise JWT Bearer Token Authentication Middleware
 * Inspects Authorization: Bearer <token>, validates HS256 signature, and injects auth context.
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->getBearerToken();

        if ($token === null || trim($token) === '') {
            Response::unauthorized('Authentication token missing. Provide Authorization: Bearer <token>.');
            return null;
        }

        $payload = $this->jwtService->validateToken($token);

        if ($payload === null) {
            Response::unauthorized('Invalid or expired authentication token.');
            return null;
        }

        // Attach verified user and company context directly to request
        $request->setAttribute('auth_user_id', $payload['sub'] ?? null);
        $request->setAttribute('auth_company_id', $payload['company_id'] ?? null);
        $request->setAttribute('auth_role', $payload['role'] ?? null);
        $request->setAttribute('auth_payload', $payload);

        return $next($request);
    }
}
