<?php

namespace App\Contracts;

interface GenukaServiceInterface
{
    /**
     * Set the access token for API requests.
     */
    public function setAccessToken(string $accessToken): self;

    /**
     * Set the company ID for API requests.
     */
    public function setCompanyId(string $companyId): self;

    /**
     * Get company information by ID.
     *
     * @throws \Exception
     */
    public function getCompany(string $companyId): array;

    /**
     * Make a GET request to the Genuka API.
     *
     * @throws \Exception
     */
    public function get(string $endpoint, array $params = []): array;

    /**
     * Make a POST request to the Genuka API.
     *
     * @throws \Exception
     */
    public function post(string $endpoint, array $data = []): array;

    /**
     * Make a PUT request to the Genuka API.
     *
     * @throws \Exception
     */
    public function put(string $endpoint, array $data = []): array;

    /**
     * Make a DELETE request to the Genuka API.
     *
     * @throws \Exception
     */
    public function delete(string $endpoint): array;
}
