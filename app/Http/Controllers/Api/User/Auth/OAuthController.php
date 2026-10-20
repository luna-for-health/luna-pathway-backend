<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OAuthSignupRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\OAuthService;
use App\Services\Luna\LunaUserService;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;

#[Group('Authentication')]
class OAuthController extends Controller
{
    use ApiResponser;

    /**
     * Sign up (or log in) with an OAuth provider
     *
     * Verifies the client-obtained access token with the provider (`google` or `apple`)
     * and either creates a new account or logs into the existing one for that email.
     * `first_name`/`last_name`/`phone`/`avatar` are only used when creating a new account.
     */
    #[Response(200, 'Existing account: logged in', type: 'array{status: true, message: string, data: array{user: UserResource, access_token: string}}')]
    #[Response(201, 'New account created', type: 'array{status: true, message: string, data: array{user: UserResource, access_token: string}}')]
    #[Response(400, 'Provider rejected the access token', type: 'array{status: false, message: string, data: null, errors: null}')]
    #[Response(422, 'Unsupported provider', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function signup(OAuthSignupRequest $request, string $provider, LunaUserService $lunaUserService): JsonResponse
    {
        if (! in_array($provider, OAuthService::PROVIDERS, true)) {
            return $this->errorResponse(__('auth.oauth_provider_error'), 422);
        }

        $socialUser = OAuthService::getSocialUser($provider, $request->validated('access_token'));

        if (! $socialUser || ! $socialUser->getEmail()) {
            return $this->errorResponse(__('auth.oauth_provider_error'), 400);
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            if (! $user->isOauthUser()) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'is_private_email' => OAuthService::isPrivateEmail($provider, $socialUser),
                ]);
            }

            $token = $user->createToken('Personal Access Token');

            return $this->successResponse([
                'user' => new UserResource($user),
                'access_token' => $token->plainTextToken,
            ], __('auth.login_success'));
        }

        [$firstName, $lastName] = $this->resolveName($request, $socialUser);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $socialUser->getEmail(),
            'phone' => $request->validated('phone'),
            'avatar_id' => $request->validated('avatar') ?? 1,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'is_private_email' => OAuthService::isPrivateEmail($provider, $socialUser),
            'has_accepted_gdpr' => false,
            'has_completed_onboarding' => false,
        ]);

        $user->forceFill(['email_verified_at' => Carbon::now()])->save();

        $lunaUserService->linkUser($user);

        $token = $user->createToken('Personal Access Token');

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $token->plainTextToken,
        ], __('auth.registration_success'), 201);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function resolveName(OAuthSignupRequest $request, SocialiteUser $socialUser): array
    {
        if ($request->validated('first_name')) {
            return [$request->validated('first_name'), $request->validated('last_name')];
        }

        $fullName = trim((string) $socialUser->getName()) ?: strstr($socialUser->getEmail(), '@', true);
        $parts = preg_split('/\s+/', $fullName, 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
