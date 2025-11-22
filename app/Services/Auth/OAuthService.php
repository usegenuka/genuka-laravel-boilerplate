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
     * @param string $code
     * @param string $companyId
     * @param string $timestamp
     * @param string $hmac
     * @return Company
     * @throws \Exception
     */
    public function handleCallback(
        string $code,
        string $companyId,
        string $timestamp,
        string $hmac
    ): Company {
        try {
            // Validate HMAC signature
            $this->validateHmac($code, $companyId, $timestamp, $hmac);

            // Exchange authorization code for access token
            $accessToken = $this->exchangeCodeForToken($code);

            // Fetch company information from Genuka API
            $companyData = $this->fetchCompanyInfo($companyId, $accessToken);

            // Store or update company in database
            $company = $this->storeCompany($companyId, $code, $accessToken, $companyData);

            Log::info('OAuth callback completed successfully', [
                'company_id' => $companyId,
                'company_name' => $company->name,
            ]);

            return $company;
        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate HMAC signature for security.
     *
     * @param string $code
     * @param string $companyId
     * @param string $timestamp
     * @param string $hmac
     * @return void
     * @throws \Exception
     */
    protected function validateHmac(
        string $code,
        string $companyId,
        string $timestamp,
        string $hmac
    ): void {
        // Build the message to verify
        $message = $code . $companyId . $timestamp;

        // Calculate expected HMAC
        $expectedHmac = hash_hmac('sha256', $message, config('genuka.client_secret'));

        // Compare HMACs in constant time to prevent timing attacks
        if (!hash_equals($expectedHmac, $hmac)) {
            throw new \Exception('Invalid HMAC signature');
        }

        // Validate timestamp (within 5 minutes)
        $currentTime = time();
        $requestTime = (int) $timestamp;
        $timeDifference = abs($currentTime - $requestTime);

        if ($timeDifference > 300) {
            throw new \Exception('Request timestamp expired');
        }
    }

    /**
     * Exchange authorization code for access token.
     *
     * @param string $code
     * @return string
     * @throws \Exception
     */
    protected function exchangeCodeForToken(string $code): string
    {
        $response = Http::asForm()->post(config('genuka.url') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => config('genuka.client_id'),
            'client_secret' => config('genuka.client_secret'),
            'redirect_uri' => config('genuka.redirect_uri'),
        ]);

        if (!$response->successful()) {
            Log::error('Token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
            throw new \Exception('Access token not found in response');
        }

        return $data['access_token'];
    }

    /**
     * Fetch company information from Genuka API.
     *
     * @param string $companyId
     * @param string $accessToken
     * @return array
     * @throws \Exception
     */
    protected function fetchCompanyInfo(string $companyId, string $accessToken): array
    {
        return Genuka::setAccessToken($accessToken)->getCompany($companyId);
    }

    /**
     * Store or update company in database.
     *
     * @param string $companyId
     * @param string $code
     * @param string $accessToken
     * @param array $companyData
     * @return Company
     */
    protected function storeCompany(
        string $companyId,
        string $code,
        string $accessToken,
        array $companyData
    ): Company {
        return Company::updateOrCreate(
            ['id' => $companyId],
            [
                'handle' => $companyData['handle'] ?? null,
                'name' => $companyData['name'],
                'description' => $companyData['description'] ?? null,
                'logo_url' => $companyData['logo_url'] ?? $companyData['logoUrl'] ?? null,
                'access_token' => $accessToken,
                'authorization_code' => $code,
                'phone' => $companyData['metadata']['contact'] ?? $companyData['phone'] ?? null,
            ]
        );
    }
}
