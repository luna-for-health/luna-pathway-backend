<?php

namespace App\Http\Controllers\Api\Secretary\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\SecretaryLoginRequest;
use App\Http\Resources\Secretary\SecretaryResource;
use App\Models\Secretary;
use App\Traits\ApiResponser;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Support\Facades\Hash;

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
}
