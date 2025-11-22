<?php

namespace App\Services\Genuka;

use App\Contracts\GenukaServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenukaService implements GenukaServiceInterface
{
    protected ?string $accessToken = null;
    protected string $baseUrl;

    /**
     * Create a new Genuka service instance.
     */
    public function __construct()
    {
        $this->baseUrl = config('genuka.url');
    }

    /**
     * Set the access token for API requests.
     *
     * @param string $accessToken
     * @return self
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Get company information by ID.
     *
     * @param string $companyId
     * @return array
     * @throws \Exception
     */
    public function getCompany(string $companyId): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/api/companies/{$companyId}");

        if (!$response->successful()) {
            Log::error('Failed to fetch company info', [
                'company_id' => $companyId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to fetch company information: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Make a GET request to the Genuka API.
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function get(string $endpoint, array $params = []): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$endpoint}", $params);

        if (!$response->successful()) {
            Log::error('Genuka API GET request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Genuka API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Make a POST request to the Genuka API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function post(string $endpoint, array $data = []): array
    {
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$endpoint}", $data);

        if (!$response->successful()) {
            Log::error('Genuka API POST request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Genuka API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Make a PUT request to the Genuka API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function put(string $endpoint, array $data = []): array
    {
        $response = Http::withToken($this->accessToken)
            ->put("{$this->baseUrl}/{$endpoint}", $data);

        if (!$response->successful()) {
            Log::error('Genuka API PUT request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Genuka API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Make a DELETE request to the Genuka API.
     *
     * @param string $endpoint
     * @return array
     * @throws \Exception
     */
    public function delete(string $endpoint): array
    {
        $response = Http::withToken($this->accessToken)
            ->delete("{$this->baseUrl}/{$endpoint}");

        if (!$response->successful()) {
            Log::error('Genuka API DELETE request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Genuka API request failed: ' . $response->body());
        }

        return $response->json();
    }
}
