<?php
declare(strict_types=1);

namespace App\Security;

use App\Core\Env;

/**
 * Enterprise Pure PHP JWT Service (HS256)
 * Generates and validates JSON Web Tokens with zero third-party dependencies.
 */
class JwtService
{
    private string $secret;
    private string $algo;
    private int $expiration;
    private string $issuer;

    public function __construct()
    {
        $this->secret     = (string)Env::get('JWT_SECRET', 'antigravity_default_secret_key_32_characters!');
        $this->algo       = (string)Env::get('JWT_ALGO', 'HS256');
        $this->expiration = (int)Env::get('JWT_EXPIRATION', 86400);
        $this->issuer     = (string)Env::get('JWT_ISSUER', 'antigravity_erp');
    }

    /**
     * Generate a new JWT token
     */
    public function generateToken(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algo,
        ];

        $now = time();
        $defaultPayload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $this->expiration,
        ];

        $mergedPayload = array_merge($defaultPayload, $payload);

        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($mergedPayload));

        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    /**
     * Validate and decode a JWT token
     * Returns decoded payload array or null if invalid/expired
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $this->secret, true);
        $providedSignature = $this->base64UrlDecode($base64Signature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null; // Signature mismatch
        }

        // Decode payload
        $payloadJson = $this->base64UrlDecode($base64Payload);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Expired
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
