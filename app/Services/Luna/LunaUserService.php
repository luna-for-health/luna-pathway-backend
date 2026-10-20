<?php

namespace App\Services\Luna;

use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LunaUserService
{
    public static function isConfigured(): bool
    {
        return filled(config('services.luna.base_url')) && filled(config('services.luna.token'));
    }

    /**
     * Store the matching Luna user id on the user. Failures are logged and never thrown,
     * so account creation is not blocked when Luna is unreachable; `luna:sync-users` retries them.
     */
    public function linkUser(User $user): void
    {
        if ($user->luna_user_id) {
            return;
        }

        if (! self::isConfigured()) {
            Log::warning('Luna API is not configured, skipping Luna user link', ['user_id' => $user->id]);

            return;
        }

        try {
            $user->forceFill(['luna_user_id' => $this->resolveUserId($user)])->save();
        } catch (Throwable $exception) {
            Log::error('Luna user link failed', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Find the Luna user matching this user's email, creating it when missing, and return its id.
     *
     * @throws RequestException
     */
    public function resolveUserId(User $user): int
    {
        $response = Http::baseUrl(config('services.luna.base_url'))
            ->withHeaders(['X-Internal-Token' => config('services.luna.token')])
            ->acceptJson()
            ->timeout(config('services.luna.timeout'))
            ->post('/api/internal/doctors/users/resolve', [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'birthdate' => $user->birthdate?->toDateString(),
            ])
            ->throw();

        return (int) $response->json('data.id');
    }
}
