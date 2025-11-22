<?php

namespace App\Contracts;

interface GenukaServiceInterface
{
    /**
     * Set the access token for API requests.
     *
     * @param string $accessToken
     * @return self
     */
    public function setAccessToken(string $accessToken): self;

    /**
     * Get company information by ID.
     *
     * @param string $companyId
     * @return array
     * @throws \Exception
     */
    public function getCompany(string $companyId): array;

    /**
     * Make a GET request to the Genuka API.
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function get(string $endpoint, array $params = []): array;

    /**
     * Make a POST request to the Genuka API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function post(string $endpoint, array $data = []): array;

    /**
     * Make a PUT request to the Genuka API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function put(string $endpoint, array $data = []): array;

    /**
     * Make a DELETE request to the Genuka API.
     *
     * @param string $endpoint
     * @return array
     * @throws \Exception
     */
    public function delete(string $endpoint): array;
}
