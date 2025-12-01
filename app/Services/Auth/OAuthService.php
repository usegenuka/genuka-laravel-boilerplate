<?php

namespace App\Services\Auth;

use App\Facades\Genuka;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OAuthService
{
    /**
     * Handle OAuth callback and complete authorization flow.
     *
     * @throws \Exception
     */
    public function handleCallback(
        string $code,
        string $companyId,
        string $timestamp,
        string $hmac,
        string $redirectTo,
    ): Company {
        try {
            // Validate HMAC signature
            $this->validateHmac(
                $code,
                $companyId,
                $timestamp,
                $hmac,
                $redirectTo,
            );

            // Exchange authorization code for tokens
            $tokenData = $this->exchangeCodeForToken($code);

            // Fetch company information from Genuka API
            $companyData = $this->fetchCompanyInfo(
                $companyId,
                $tokenData["access_token"],
            );

            // Store or update company in database
            $company = $this->storeCompany(
                $companyId,
                $code,
                $tokenData,
                $companyData,
            );

            return $company;
        } catch (\Exception $e) {
            Log::error("OAuth callback failed", [
                "company_id" => $companyId,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate HMAC signature for security.
     *
     * @param  string  $redirectTo  Keep URL-encoded value as received
     *
     * @throws \Exception
     */
    protected function validateHmac(
        string $code,
        string $companyId,
        string $timestamp,
        string $hmac,
        string $redirectTo,
    ): void {
        // Keep redirect_to as-is (already URL-encoded once by Genuka)
        // http_build_query will encode it a second time, matching Genuka's double encoding
        $params = [
            "code" => $code,
            "company_id" => $companyId,
            "redirect_to" => $redirectTo, // Already encoded, keep as-is
            "timestamp" => $timestamp,
        ];

        // Sort alphabetically (same as Genuka)
        ksort($params);

        // Build query string (http_build_query will encode redirect_to again = double encoding)
        $queryString = http_build_query($params);

        $expectedHmac = hash_hmac(
            "sha256",
            $queryString,
            config("genuka.client_secret"),
        );

        if (!hash_equals($expectedHmac, $hmac)) {
            throw new \Exception("Invalid HMAC signature");
        }

        // Validate timestamp (within 5 minutes)
        $currentTime = time();
        $requestTime = (int) $timestamp;
        $timeDifference = abs($currentTime - $requestTime);

        if ($timeDifference > 300) {
            throw new \Exception("Request timestamp expired");
        }
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in_minutes: int}
     *
     * @throws \Exception
     */
    protected function exchangeCodeForToken(string $code): array
    {
        $http = Http::asForm();

        // Disable SSL verification for local development
        if (app()->environment("local")) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post(config("genuka.url") . "/oauth/token", [
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => config("genuka.client_id"),
            "client_secret" => config("genuka.client_secret"),
            "redirect_uri" => config("genuka.redirect_uri"),
        ]);

        if (!$response->successful()) {
            Log::error("Token exchange failed", [
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Failed to exchange code for token: " . $response->body(),
            );
        }

        $data = $response->json();

        if (!isset($data["access_token"])) {
            throw new \Exception("Access token not found in response");
        }

        return [
            "access_token" => $data["access_token"],
            "refresh_token" => $data["refresh_token"] ?? null,
            "expires_in_minutes" => $data["expires_in_minutes"] ?? 60,
        ];
    }

    /**
     * Fetch company information from Genuka API.
     *
     * @throws \Exception
     */
    protected function fetchCompanyInfo(
        string $companyId,
        string $accessToken,
    ): array {
        return Genuka::setAccessToken($accessToken)
            ->setCompanyId($companyId)
            ->getCompany($companyId);
    }

    /**
     * Store or update company in database.
     *
     * @param  array{access_token: string, refresh_token: string|null, expires_in_minutes: int}  $tokenData
     */
    protected function storeCompany(
        string $companyId,
        string $code,
        array $tokenData,
        array $companyData,
    ): Company {
        return Company::updateOrCreate(
            ["id" => $companyId],
            [
                "handle" => $companyData["handle"] ?? null,
                "name" => $companyData["name"],
                "description" => $companyData["description"] ?? null,
                "logo_url" =>
                    $companyData["logo_url"] ??
                    ($companyData["logoUrl"] ?? null),
                "access_token" => $tokenData["access_token"],
                "refresh_token" => $tokenData["refresh_token"],
                "token_expires_at" => now()->addMinutes($tokenData["expires_in_minutes"]),
                "authorization_code" => $code,
                "phone" =>
                    $companyData["metadata"]["contact"] ??
                    ($companyData["phone"] ?? null),
            ],
        );
    }
}
