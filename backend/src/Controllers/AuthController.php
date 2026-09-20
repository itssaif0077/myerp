<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Security\JwtService;
use PDO;
use Throwable;

/**
 * Enterprise Authentication Controller
 * Manages secure user login, JWT generation, activity logging, and token verification.
 */
class AuthController
{
    private PDO $db;
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->db = Database::getConnection();
        $this->jwtService = $jwtService;
    }

    /**
     * POST /api/v1/auth/login
     * Authenticate user credentials and return signed JWT Bearer Token
     */
    public function login(Request $request): void
    {
        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');

        if ($email === '' || $password === '') {
            Response::unprocessable('Email and password are required.');
            return;
        }

        try {
            // 1. Fetch active user by email
            $stmt = $this->db->prepare("
                SELECT u.id, u.company_id, u.name, u.email, u.password, u.role, u.status,
                       c.name AS company_name
                FROM users u
                LEFT JOIN companies c ON c.id = u.company_id
                WHERE u.email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                Response::unauthorized('Invalid email or password.');
                return;
            }

            if ($user['status'] !== 'active') {
                Response::forbidden('Your account is inactive. Please contact your administrator.');
                return;
            }

            // 2. Log login in activity_logs
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $logStmt = $this->db->prepare("
                INSERT INTO activity_logs (company_id, user_id, action, description, ip_address)
                VALUES (:cid, :uid, 'LOGIN', :desc, :ip)
            ");
            $logStmt->execute([
                'cid'  => $user['company_id'],
                'uid'  => $user['id'],
                'desc' => "User {$user['email']} logged in successfully",
                'ip'   => $ipAddress,
            ]);

            // 3. Generate JWT Token (claims: sub, company_id, role, email)
            $token = $this->jwtService->generateToken([
                'sub'        => (int)$user['id'],
                'company_id' => (int)$user['company_id'],
                'role'       => $user['role'],
                'email'      => $user['email'],
                'name'       => $user['name'],
            ]);

            // 4. Return token & user profile
            Response::ok([
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400, // 24 Hours
                'user'       => [
                    'id'           => (int)$user['id'],
                    'name'         => $user['name'],
                    'email'        => $user['email'],
                    'role'         => $user['role'],
                    'company_id'   => (int)$user['company_id'],
                    'company_name' => $user['company_name'] ?? 'Main Company',
                ]
            ], 'Login successful');
        } catch (Throwable $e) {
            Response::serverError('Login processing failed: ' . $e->getMessage());
        }
    }
}
