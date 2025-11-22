<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Contracts\GenukaServiceInterface setAccessToken(string $accessToken)
 * @method static array getCompany(string $companyId)
 * @method static array get(string $endpoint, array $params = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 *
 * @see \App\Services\Genuka\GenukaService
 */
class Genuka extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'genuka';
    }
}
