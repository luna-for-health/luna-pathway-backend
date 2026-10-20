<?php

namespace App\Http\Controllers\Api\User\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\UpdateOnboardingRequest;
use App\Http\Resources\Onboarding\OnboardingResource;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponser;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group('Onboarding')]
class OnboardingController extends Controller
{
    use ApiResponser;

    /**
     * Get onboarding content
     *
     * Returns the onboarding carousel steps (title, copy, background color, icon/media
     * asset URLs). Public — no authentication required.
     */
    #[Response(200, 'Onboarding content', type: 'array{status: true, message: string, data: list<array{step: int, title: string|null, backgroundColor: string|null, description: string|null, paragraphs: string[]|null, icon: string|null, media: string|null}>}')]
    public function content(): JsonResponse
    {
        return $this->successResponse(new OnboardingResource(collect()), __('onboarding.content_retrieved'));
    }

    /**
     * Complete onboarding
     *
     * Marks the authenticated user as having completed onboarding, optionally updating
     * their first_name/last_name/phone/avatar at the same time. Returns a fresh token
     * alongside the user.
     */
    #[Response(200, 'Onboarding completed', type: 'array{status: true, message: string, data: array{user: UserResource, access_token: string}}')]
    public function update(UpdateOnboardingRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update(array_filter([
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'phone' => $request->validated('phone'),
            'avatar_id' => $request->validated('avatar'),
            'has_completed_onboarding' => true,
        ], fn ($value) => ! is_null($value)));

        $token = $user->createToken('Personal Access Token');

        return $this->successResponse([
            'user' => new UserResource($user),
            'access_token' => $token->plainTextToken,
        ], __('onboarding.updated'));
    }
}
