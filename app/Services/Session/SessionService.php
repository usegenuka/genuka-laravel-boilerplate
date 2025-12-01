<?php

namespace App\Services\Session;

use App\Models\Company;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class SessionService
{
    private const SESSION_COOKIE_NAME = 'session';

    private const REFRESH_COOKIE_NAME = 'refresh_session';

    private const SESSION_MAX_AGE = 60 * 60 * 7; // 7 hours in seconds

    private const REFRESH_MAX_AGE = 60 * 60 * 24 * 30; // 30 days in seconds

    /**
     * Create both session and refresh cookies for a company.
     * Double cookie pattern for secure session management.
     *
     * @return string Session JWT token
     */
    public function createSession(string $companyId): string
    {
        $secret = config('genuka.client_secret');
        $isProduction = config('app.env') === 'production';

        // Create session token (short-lived: 7h)
        $sessionPayload = [
            'companyId' => $companyId,
            'type' => 'session',
            'iat' => time(),
            'exp' => time() + self::SESSION_MAX_AGE,
        ];
        $sessionToken = JWT::encode($sessionPayload, $secret, 'HS256');

        // Create refresh token (long-lived: 30 days)
        $refreshPayload = [
            'companyId' => $companyId,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + self::REFRESH_MAX_AGE,
        ];
        $refreshToken = JWT::encode($refreshPayload, $secret, 'HS256');

        // Set session cookie (7h)
        Cookie::queue(
            self::SESSION_COOKIE_NAME,
            $sessionToken,
            self::SESSION_MAX_AGE / 60, // Laravel uses minutes
            '/',
            null,
            $isProduction,
            true, // httpOnly
            false,
            'lax'
        );

        // Set refresh cookie (30 days)
        Cookie::queue(
            self::REFRESH_COOKIE_NAME,
            $refreshToken,
            self::REFRESH_MAX_AGE / 60, // Laravel uses minutes
            '/',
            null,
            $isProduction,
            true, // httpOnly
            false,
            'lax'
        );

        return $sessionToken;
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
            // Don't log expected expiration errors
            $isExpiredError = str_contains($e->getMessage(), 'Expired');
            if (! $isExpiredError) {
                Log::error('JWT verification failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Verify refresh token and return companyId.
     * This is used for secure session refresh.
     *
     * @return string|null Company ID or null if invalid
     */
    public function verifyRefreshToken(): ?string
    {
        $token = Cookie::get(self::REFRESH_COOKIE_NAME);

        if (! $token) {
            return null;
        }

        $payload = $this->verifyJwt($token);

        // Ensure it's a refresh token, not a session token
        if (! $payload || ! isset($payload->type) || $payload->type !== 'refresh') {
            return null;
        }

        return $payload->companyId ?? null;
    }

    /**
     * Get current company ID from session.
     *
     * @return string|null Company ID or null if not authenticated
     */
    public function getCurrentCompanyId(): ?string
    {
        $token = Cookie::get(self::SESSION_COOKIE_NAME);

        if (! $token) {
            return null;
        }

        $payload = $this->verifyJwt($token);

        if (! $payload || ! isset($payload->type) || $payload->type !== 'session') {
            return null;
        }

        return $payload->companyId ?? null;
    }

    /**
     * Get authenticated company from current request.
     *
     * @return Company|null Company model or null if not authenticated
     */
    public function getAuthenticatedCompany(): ?Company
    {
        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            return null;
        }

        return Company::find($companyId);
    }

    /**
     * Check if user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->getCurrentCompanyId() !== null;
    }

    /**
     * Destroy both session and refresh cookies (logout).
     */
    public function destroySession(): void
    {
        Cookie::queue(Cookie::forget(self::SESSION_COOKIE_NAME));
        Cookie::queue(Cookie::forget(self::REFRESH_COOKIE_NAME));
    }

    /**
     * Get the session cookie name.
     */
    public static function getSessionCookieName(): string
    {
        return self::SESSION_COOKIE_NAME;
    }

    /**
     * Get the refresh cookie name.
     */
    public static function getRefreshCookieName(): string
    {
        return self::REFRESH_COOKIE_NAME;
    }
}
