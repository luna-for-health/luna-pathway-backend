<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gdpr\GdprConsentRequest;
use App\Http\Resources\Gdpr\GdprConsentResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponser;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('GDPR')]
class GdprController extends Controller
{
    use ApiResponser;

    /**
     * Get GDPR consent content
     *
     * Returns the GDPR consent carousel steps for the given email, plus whether that
     * email's account has already accepted the GDPR terms (`user` is `null` when no
     * account exists). Public — no authentication required.
     */
    #[Response(200, 'GDPR consent content', type: 'array{status: true, message: string, data: array{user: array{has_accepted_gdpr: bool}|null, gdpr: list<array{step: int, title: string|null, backgroundColor: string|null, paragraphs: string[]|null, icon: string|null, media: string|null, media_text: string|null, link: string|null}>}}')]
    public function consent(GdprConsentRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        return $this->successResponse([
            'user' => $user ? ['has_accepted_gdpr' => $user->has_accepted_gdpr] : null,
            'gdpr' => new GdprConsentResource(collect()),
        ], __('gdpr-consent.retrieved'));
    }

    /**
     * Accept GDPR terms
     *
     * Marks the authenticated user as having accepted the GDPR terms.
     */
    #[Response(200, 'GDPR terms accepted', type: 'array{status: true, message: string, data: UserResource}')]
    public function accept(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['has_accepted_gdpr' => true]);

        return $this->successResponse(new UserResource($user), __('auth.gdpr_accepted'));
    }
}
