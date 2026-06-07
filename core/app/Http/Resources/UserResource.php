<?php

namespace App\Http\Resources;

use App\Constants\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors the User schema in core/docs/api/openapi-v1.yaml.
 *
 * Translates the database flags (ev / sv / ts) into the cleaner booleans the
 * mobile client expects, and exposes `balance` (which is in $hidden on the
 * User model for the web flow but is safe to return to the owning user).
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id'                 => $user->id,
            'username'           => (string) ($user->username ?? ''),
            'email'              => $user->email,
            'firstname'          => (string) ($user->firstname ?? ''),
            'lastname'           => (string) ($user->lastname ?? ''),
            'avatar'             => null, // user-avatar upload not yet implemented on web; placeholder for symmetry.
            'balance'            => (float) $user->balance,
            'email_verified'     => (int) $user->ev === Status::VERIFIED,
            'mobile_verified'    => (int) $user->sv === Status::VERIFIED,
            'two_factor_enabled' => (int) $user->ts === Status::ENABLE,
        ];
    }
}
