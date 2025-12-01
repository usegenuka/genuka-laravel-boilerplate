<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $handle
 * @property string $name
 * @property string|null $description
 * @property string|null $logo_url
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property \Illuminate\Support\Carbon|null $token_expires_at
 * @property string|null $authorization_code
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Company extends Model
{
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'handle',
        'name',
        'description',
        'logo_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'authorization_code',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
        'authorization_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the access token attribute with automatic decryption.
     *
     * @param  string|null  $value
     */
    public function getAccessTokenAttribute($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return config('genuka.encrypt_tokens')
            ? decrypt($value)
            : $value;
    }

    /**
     * Set the access token attribute with automatic encryption.
     *
     * @param  string|null  $value
     */
    public function setAccessTokenAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['access_token'] = null;

            return;
        }

        $this->attributes['access_token'] = config('genuka.encrypt_tokens')
            ? encrypt($value)
            : $value;
    }

    /**
     * Get the refresh token attribute with automatic decryption.
     *
     * @param  string|null  $value
     */
    public function getRefreshTokenAttribute($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return config('genuka.encrypt_tokens')
            ? decrypt($value)
            : $value;
    }

    /**
     * Set the refresh token attribute with automatic encryption.
     *
     * @param  string|null  $value
     */
    public function setRefreshTokenAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['refresh_token'] = null;

            return;
        }

        $this->attributes['refresh_token'] = config('genuka.encrypt_tokens')
            ? encrypt($value)
            : $value;
    }
}
