<?php

namespace App\Services\Session;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class SessionService
{
    private const SESSION_COOKIE_NAME = 'genuka_session';

    private const SESSION_MAX_AGE = 60 * 60 * 7; // 7 hours in seconds

    /**
     * Create a JWT session for a company.
     *
     * Similar to Next.js createSession:
     * - Uses JWT with HS256 algorithm
     * - Stores in httpOnly cookie
     * - 7 hours expiration
     *
     * @return string JWT token
     */
    public function createSession(string $companyId): string
    {
        $secret = config('genuka.client_secret');
        $isProduction = config('app.env') === 'production';

        // Create JWT payload
        $payload = [
            'companyId' => $companyId,
            'iat' => time(), // Issued at
            'exp' => time() + self::SESSION_MAX_AGE, // Expiration (7 hours)
        ];

        // Sign JWT with HS256
        $token = JWT::encode($payload, $secret, 'HS256');

        // Set httpOnly cookie
        Cookie::queue(
            self::SESSION_COOKIE_NAME,
            $token,
            self::SESSION_MAX_AGE / 60, // Laravel uses minutes
            '/', // path
            null, // domain
            $isProduction, // secure (HTTPS only in production)
            true, // httpOnly
            false, // raw
            'lax' // sameSite
        );

        return $token;
    }

    /**
     * Verify and decode JWT token.
     *
     * @return object|null Decoded payload or null if invalid
     */
    public function verifyJwt(string $token): ?object
    {
        try {
            $secret = config('genuka.client_secret');
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            return $decoded;
        } catch (\Exception $e) {
            Log::error('JWT verification failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get authenticated company from current request.
     *
     * @return object|null Company data or null if not authenticated
     */
    public function getAuthenticatedCompany(): ?object
    {
        $token = Cookie::get(self::SESSION_COOKIE_NAME);

        if (! $token) {
            return null;
        }

        $payload = $this->verifyJwt($token);

        if (! $payload || ! isset($payload->companyId)) {
            return null;
        }

        // Fetch company from database
        $company = \App\Models\Company::find($payload->companyId);

        if (! $company) {
            return null;
        }

        return (object) [
            'id' => $company->id,
            'handle' => $company->handle,
            'name' => $company->name,
            'description' => $company->description,
            'logo_url' => $company->logo_url,
            'phone' => $company->phone,
        ];
    }

    /**
     * Destroy the current session.
     */
    public function destroySession(): void
    {
        Cookie::queue(Cookie::forget(self::SESSION_COOKIE_NAME));
    }

    /**
     * Get the session cookie name.
     */
    public static function getSessionCookieName(): string
    {
        return self::SESSION_COOKIE_NAME;
    }
}
