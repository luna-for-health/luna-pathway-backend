<?php

namespace App\Services\Auth;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class OAuthService
{
    /**
     * Supported OAuth providers.
     *
     * @var string[]
     */
    public const PROVIDERS = ['google', 'apple'];

    public static function getSocialUser(string $provider, ?string $accessToken): ?SocialiteUser
    {
        if (! $accessToken || ! in_array($provider, self::PROVIDERS, true)) {
            return null;
        }

        try {
            return Socialite::driver($provider)->userFromToken($accessToken);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function isPrivateEmail(string $provider, SocialiteUser $socialUser): bool
    {
        return $provider === 'apple' && (($socialUser->user['is_private_email'] ?? null) === 'true');
    }
}
