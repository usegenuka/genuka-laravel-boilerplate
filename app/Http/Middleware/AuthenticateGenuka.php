<?php

namespace App\Http\Middleware;

use App\Services\Session\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateGenuka
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Verifies JWT token from cookie and ensures user is authenticated.
     * Similar to Next.js middleware that checks session cookie.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->sessionService->getAuthenticatedCompany();

        if (! $company) {
            abort(401, 'Please authenticate with Genuka first.');
        }

        // Add company to request attributes (not request parameters)
        $request->attributes->set('genuka_company', $company);

        return $next($request);
    }
}
