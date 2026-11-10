<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendCodeRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Http\Resources\UserResource;
use App\Mail\MagicCodeMail;
use App\Models\MagicCode;
use App\Models\User;
use App\Services\Luna\LunaUserService;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Support\Facades\Mail;

/**
 * Passwordless authentication: request a 6-digit code by email, then verify it to receive
 * a Sanctum bearer token. No passwords are involved anywhere in this flow.
 */
#[Group('Authentication')]
class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Send a login/signup code
     *
     * Emails a 6-digit code to the given address, valid for 30 minutes. In the local
     * environment the code is always `123456` to ease testing.
     */
    #[Response(200, 'Code sent', type: 'array{status: true, message: string, data: null}')]
    #[Response(404, 'No account found for this email (only when type=login)', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function sendCode(SendCodeRequest $request)
    {
        $email = $request->validated('email');
        $type = $request->validated('type');

        $code = app()->environment('local') ? '123456' : (string) random_int(100000, 999999);

        if ($type === 'login' && ! User::where('email', $email)->exists()) {
            return $this->errorResponse(__('auth.not_found'), 404);
        }

        MagicCode::where('email', $email)->orWhere('code', $code)->delete();

        MagicCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(30),
        ]);

        Mail::to($email)->send(new MagicCodeMail($code));

        return $this->successResponse(null, __('auth.magic_code_sent'));
    }

    /**
     * Verify a code and sign in (or sign up)
     *
     * Verifies the code sent to the email. If no user exists for that email yet, one is
     * created automatically and treated as verified. Either way, a bearer token is issued.
     */
    #[Response(200, 'Code verified, user authenticated', type: 'array{status: true, message: string, data: array{user: UserResource, access_token: string, expires_at: string|null}}')]
    #[Response(422, 'Invalid or expired code', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function verifyCode(VerifyCodeRequest $request, LunaUserService $lunaUserService)
    {
        $email = $request->validated('email');
        $code = $request->validated('code');

        $magicCode = MagicCode::where('email', $email)->latest('id')->first();

        if (! $magicCode) {
            return $this->errorResponse(__('auth.invalid_code'), 422);
        }

        if ($magicCode->expires_at->isPast()) {
            $magicCode->delete();

            return $this->errorResponse(__('auth.expired_code'), 422);
        }

        if (! hash_equals($magicCode->code, $code)) {
            $magicCode->increment('attempts');

            if ($magicCode->attempts >= MagicCode::MAX_ATTEMPTS) {
                $magicCode->delete();
            }

            return $this->errorResponse(__('auth.invalid_code'), 422);
        }

        $magicCode->delete();

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'first_name' => strstr($email, '@', true) ?: $email,
                'email' => $email,
                'avatar_id' => 1,
                'has_accepted_gdpr' => true,
                'has_completed_onboarding' => false,
            ]);

            $user->forceFill(['email_verified_at' => Carbon::now()])->save();

            $lunaUserService->linkUser($user);
        }

        $token = $user->createToken('Personal Access Token');

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ], __('auth.verification_success'));
    }

    /**
     * Create or update a profile
     *
     * Creates a new user (and issues a token), or updates an existing one when `user_id`
     * is passed. No code is required here — pair it with verify-code/login in front of it
     * if the caller isn't already authenticated.
     */
    #[Response(200, 'User registered/updated', type: 'array{status: true, message: string, data: array{user: UserResource, access_token: string, expires_at: string|null}}')]
    #[Response(404, 'user_id was passed but no matching user exists', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function register(RegisterRequest $request, LunaUserService $lunaUserService)
    {
        $data = $request->validated();
        $userId = $data['user_id'] ?? null;

        $user = $userId ? User::find($userId) : null;

        if ($userId && ! $user) {
            return $this->errorResponse(__('User not found'), 404);
        }

        if ($user) {
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? $user->phone,
                'birthdate' => $data['birthdate'] ?? $user->birthdate,
                'location' => $data['location'] ?? $user->location,
                'avatar_id' => $data['avatar'] ?? $user->avatar_id,
                'has_completed_onboarding' => true,
            ]);
        } else {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
                'location' => $data['location'] ?? null,
                'avatar_id' => $data['avatar'] ?? 1,
                'has_completed_onboarding' => true,
            ]);

            $user->forceFill(['email_verified_at' => Carbon::now()])->save();

            $lunaUserService->linkUser($user);
        }

        $token = $user->createToken('Personal Access Token');

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ], __('auth.registration_success'));
    }

    /**
     * Log out
     *
     * Revokes the bearer token used to make this request.
     */
    #[Response(200, 'Logged out', type: 'array{status: true, message: string, data: null}')]
    public function logout()
    {
        $user = auth()->user();
        $user->currentAccessToken()?->delete();

        return $this->successResponse(null, __('auth.logout_success'));
    }
}
