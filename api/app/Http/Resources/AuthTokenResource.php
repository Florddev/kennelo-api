<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class AuthTokenResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(User $user, protected string $accessToken, protected ?string $refreshToken = null)
    {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $this->id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'locale' => $this->locale,
                'is_id_verified' => $this->is_id_verified,
                'email_verified_at' => $this->email_verified_at,
                'roles' => $this->roles->pluck('name'),
            ],
        ];
    }
}
