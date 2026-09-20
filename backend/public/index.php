<?php
declare(strict_types=1);

/**
 * Antigravity ERP - High-Performance API Front Controller
 * Single Entry Point for all REST API traffic.
 */

// 1. Timezone & Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// 2. PSR-4 Autoloader
require_once dirname(__DIR__) . '/src/autoload.php';

use App\Core\Env;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\JwtAuthMiddleware;
use App\Controllers\HealthController;
use App\Security\JwtService;

// 3. Load Environment Variables
Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set((string)Env::get('APP_TIMEZONE', 'Asia/Kolkata'));

// 4. Global Exception Handler (Always output standard JSON, never leak fatal HTML)
set_exception_handler(function (Throwable $e): void {
    $isDebug = (bool)Env::get('APP_DEBUG', false);
    Response::serverError(
        $isDebug ? $e->getMessage() : 'An internal server error occurred.',
        $isDebug ? [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ] : null
    );
});

// 5. Initialize IoC Container & Dependency Injections
$container = Container::getInstance();
$container->singleton(JwtService::class);
$container->singleton(Router::class, fn($c) => new Router($c));

// Bind Repository Interfaces to Concrete Implementations (.NET Style)
$container->bind(
    \App\Domain\Repositories\IProductRepository::class,
    \App\Infrastructure\Persistence\ProductRepository::class
);
$container->bind(
    \App\Domain\Repositories\IContactRepository::class,
    \App\Infrastructure\Persistence\ContactRepository::class
);

// 6. Setup Router & Middleware
$router = $container->make(Router::class);
$router->use(CorsMiddleware::class);

// 7. Define System Routes
$router->get('/api/v1', function (Request $request) {
    Response::ok([
        'name'    => Env::get('APP_NAME', 'Antigravity ERP API'),
        'version' => '1.0.0',
        'status'  => 'online',
        'endpoints' => [
            'health'       => 'GET /api/v1/health',
            'auth_login'   => 'POST /api/v1/auth/login',
            'contacts'     => 'GET|POST /api/v1/contacts',
            'pos_products' => 'GET /api/v1/pos/products',
            'pos_sale'     => 'POST /api/v1/pos/sale',
        ]
    ], 'Welcome to Antigravity ERP API Engine');
});

$router->get('/api/v1/health', [HealthController::class, 'check']);

// Authentication Routes (Get JWT Token)
$router->post('/api/v1/auth/login', [\App\Controllers\AuthController::class, 'login']);

// Contacts CRUD (Customers, Suppliers, Karigars, Staff - Protected by JWT)
$router->get('/api/v1/contacts', [\App\Controllers\ContactController::class, 'index'], [JwtAuthMiddleware::class]);
$router->get('/api/v1/contacts/{id}', [\App\Controllers\ContactController::class, 'show'], [JwtAuthMiddleware::class]);
$router->post('/api/v1/contacts', [\App\Controllers\ContactController::class, 'store'], [JwtAuthMiddleware::class]);
$router->put('/api/v1/contacts/{id}', [\App\Controllers\ContactController::class, 'update'], [JwtAuthMiddleware::class]);
$router->delete('/api/v1/contacts/{id}', [\App\Controllers\ContactController::class, 'destroy'], [JwtAuthMiddleware::class]);

// POS Quick-Sell Endpoints (Protected by JWT)
$router->get('/api/v1/pos/products', [\App\Controllers\PosController::class, 'getQuickProducts'], [JwtAuthMiddleware::class]);
$router->post('/api/v1/pos/sale', [\App\Controllers\PosController::class, 'createQuickSale'], [JwtAuthMiddleware::class]);


// Example protected route testing JWT
$router->get('/api/v1/auth/me', function (Request $request) {
    Response::ok([
        'user_id'    => $request->getAttribute('auth_user_id'),
        'company_id' => $request->getAttribute('auth_company_id'),
        'role'       => $request->getAttribute('auth_role'),
    ], 'Current authenticated profile');
}, [JwtAuthMiddleware::class]);

// 8. Capture & Dispatch Request
$request = new Request();
$router->dispatch($request);

