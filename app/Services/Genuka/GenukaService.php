<?php

namespace App\Services\Genuka;

use App\Contracts\GenukaServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenukaService implements GenukaServiceInterface
{
    protected ?string $accessToken = null;

    protected ?string $companyId = null;

    protected string $baseUrl;

    protected $httpClient = null;

    /**
     * Create a new Genuka service instance.
     */
    public function __construct()
    {
        $this->baseUrl = config("genuka.url");
    }

    /**
     * Set the access token for API requests.
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        $this->httpClient = null; // Reset client when token changes

        return $this;
    }

    /**
     * Set the company ID for API requests.
     */
    public function setCompanyId(string $companyId): self
    {
        $this->companyId = $companyId;
        $this->httpClient = null; // Reset client when company changes

        return $this;
    }

    /**
     * Get configured HTTP client instance (singleton pattern).
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function getHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        // Return existing instance if available
        if ($this->httpClient !== null) {
            return $this->httpClient;
        }

        // Create new instance
        $http = Http::withToken($this->accessToken);

        // Add X-Company header only if companyId is set
        if ($this->companyId) {
            $http = $http->withHeader("X-Company", $this->companyId);
        }

        // Disable SSL verification for local development
        if (app()->environment("local")) {
            $http = $http->withoutVerifying();
        }

        // Store and return the instance
        $this->httpClient = $http;

        return $this->httpClient;
    }

    /**
     * Get company information by ID.
     *
     * @throws \Exception
     */
    public function getCompany(string $companyId): array
    {
        $response = $this->getHttpClient()->get(
            "{$this->baseUrl}/2023-11/admin/company",
        );

        if (!$response->successful()) {
            Log::error("Failed to fetch company info", [
                "company_id" => $companyId,
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Failed to fetch company information: " . $response->body(),
            );
        }

        return $response->json();
    }

    /**
     * Make a GET request to the Genuka API.
     *
     * @throws \Exception
     */
    public function get(string $endpoint, array $params = []): array
    {
        $response = $this->getHttpClient()->get(
            "{$this->baseUrl}/{$endpoint}",
            $params,
        );

        if (!$response->successful()) {
            Log::error("Genuka API GET request failed", [
                "endpoint" => $endpoint,
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Genuka API request failed: " . $response->body(),
            );
        }

        return $response->json();
    }

    /**
     * Make a POST request to the Genuka API.
     *
     * @throws \Exception
     */
    public function post(string $endpoint, array $data = []): array
    {
        $response = $this->getHttpClient()->post(
            "{$this->baseUrl}/{$endpoint}",
            $data,
        );

        if (!$response->successful()) {
            Log::error("Genuka API POST request failed", [
                "endpoint" => $endpoint,
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Genuka API request failed: " . $response->body(),
            );
        }

        return $response->json();
    }

    /**
     * Make a PUT request to the Genuka API.
     *
     * @throws \Exception
     */
    public function put(string $endpoint, array $data = []): array
    {
        $response = $this->getHttpClient()->put(
            "{$this->baseUrl}/{$endpoint}",
            $data,
        );

        if (!$response->successful()) {
            Log::error("Genuka API PUT request failed", [
                "endpoint" => $endpoint,
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Genuka API request failed: " . $response->body(),
            );
        }

        return $response->json();
    }

    /**
     * Make a DELETE request to the Genuka API.
     *
     * @throws \Exception
     */
    public function delete(string $endpoint): array
    {
        $response = $this->getHttpClient()->delete(
            "{$this->baseUrl}/{$endpoint}",
        );

        if (!$response->successful()) {
            Log::error("Genuka API DELETE request failed", [
                "endpoint" => $endpoint,
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Genuka API request failed: " . $response->body(),
            );
        }

        return $response->json();
    }

    /**
     * Refresh access token using refresh token.
     *
     * @return array Token response with access_token, refresh_token, expires_in_minutes
     *
     * @throws \Exception
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $http = Http::asJson();

        // Disable SSL verification for local development
        if (app()->environment("local")) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post("{$this->baseUrl}/oauth/refresh", [
            "refresh_token" => $refreshToken,
            "client_id" => config("genuka.client_id"),
            "client_secret" => config("genuka.client_secret"),
        ]);

        if (!$response->successful()) {
            Log::error("Token refresh failed", [
                "status" => $response->status(),
                "body" => $response->body(),
            ]);

            throw new \Exception(
                "Failed to refresh token: " . $response->body(),
            );
        }

        return $response->json();
    }
}
