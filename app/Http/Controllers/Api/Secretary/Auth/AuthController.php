<?php

namespace App\Http\Controllers\Api\Secretary\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\ForgotSecretaryPasswordRequest;
use App\Http\Requests\Secretary\ResetSecretaryPasswordRequest;
use App\Http\Requests\Secretary\SecretaryLoginRequest;
use App\Http\Resources\Secretary\SecretaryResource;
use App\Mail\SecretaryPasswordResetMail;
use App\Models\Secretary;
use App\Models\SecretaryPasswordReset;
use App\Traits\ApiResponser;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

#[Group('Secretary')]
class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Staff login
     *
     * Authenticates a secretary/admin with email and password and returns a bearer
     * token. This token identifies a staff member, not a patient — endpoints that
     * require staff (like updating a consultation request's status) check for it.
     */
    #[Response(200, 'Logged in', type: 'array{status: true, message: string, data: array{secretary: SecretaryResource, access_token: string}}')]
    #[Response(401, 'Invalid credentials', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function login(SecretaryLoginRequest $request)
    {
        $secretary = Secretary::where('email', $request->validated('email'))->first();

        if (! $secretary || ! Hash::check($request->validated('password'), $secretary->password)) {
            return $this->errorResponse(__('auth.failed'), 401);
        }

        $token = $secretary->createToken('Secretary Access Token');

        return $this->successResponse([
            'secretary' => new SecretaryResource($secretary),
            'access_token' => $token->plainTextToken,
        ], __('auth.login_success'));
    }

    /**
     * Staff logout
     *
     * Revokes the bearer token used to make this request.
     */
    #[Response(200, 'Logged out', type: 'array{status: true, message: string, data: null}')]
    public function logout()
    {
        auth()->user()->currentAccessToken()?->delete();

        return $this->successResponse(null, __('auth.logout_success'));
    }

    /**
     * Request a password reset code
     *
     * Emails a 6-digit code to the given staff address, valid for 30 minutes. In the
     * local environment the code is always `123456` to ease testing.
     */
    #[Response(200, 'Reset code sent', type: 'array{status: true, message: string, data: null}')]
    #[Response(404, 'No staff account found for this email', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function forgotPassword(ForgotSecretaryPasswordRequest $request)
    {
        $email = $request->validated('email');

        if (! Secretary::where('email', $email)->exists()) {
            return $this->errorResponse(__('auth.not_found'), 404);
        }

        $code = app()->environment('local') ? '123456' : (string) random_int(100000, 999999);

        SecretaryPasswordReset::where('email', $email)->delete();

        SecretaryPasswordReset::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(30),
        ]);

        Mail::to($email)->send(new SecretaryPasswordResetMail($code));

        return $this->successResponse(null, __('auth.password_reset_code_sent'));
    }

    /**
     * Reset a password with a code
     *
     * Verifies the code sent to the email and sets a new password. Revokes every
     * existing bearer token for that account, so a stolen token stops working too.
     */
    #[Response(200, 'Password reset', type: 'array{status: true, message: string, data: null}')]
    #[Response(404, 'No staff account found for this email', type: 'array{status: false, message: string, data: null, errors: null}')]
    #[Response(422, 'Invalid or expired code', type: 'array{status: false, message: string, data: null, errors: null}')]
    public function resetPassword(ResetSecretaryPasswordRequest $request)
    {
        $email = $request->validated('email');

        $secretary = Secretary::where('email', $email)->first();

        if (! $secretary) {
            return $this->errorResponse(__('auth.not_found'), 404);
        }

        $reset = SecretaryPasswordReset::where('email', $email)
            ->where('code', $request->validated('code'))
            ->first();

        if (! $reset) {
            return $this->errorResponse(__('auth.invalid_code'), 422);
        }

        if ($reset->expires_at->isPast()) {
            $reset->delete();

            return $this->errorResponse(__('auth.expired_code'), 422);
        }

        $reset->delete();

        $secretary->update(['password' => $request->validated('password')]);

        $secretary->tokens()->delete();

        return $this->successResponse(null, __('auth.password_reset_success'));
    }
}
