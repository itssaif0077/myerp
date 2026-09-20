<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use Closure;

interface MiddlewareInterface
{
    /**
     * Process an incoming request
     * @param Request $request
     * @param Closure $next Next middleware or controller action
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed;
}
