<?php

namespace App\Http\Resources\Onboarding;

use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            [
                'step' => 1,
                'title' => __('onboarding.steps.step_1.title'),
                'backgroundColor' => '#FFEDCC',
                'description' => null,
                'paragraphs' => [
                    __('onboarding.steps.step_1.paragraphs.paragraph_1'),
                    __('onboarding.steps.step_1.paragraphs.paragraph_2'),
                    __('onboarding.steps.step_1.paragraphs.paragraph_3'),
                    __('onboarding.steps.step_1.paragraphs.paragraph_4'),
                ],
                'icon' => asset('images/hart.png'),
                'media' => null,
            ],
            [
                'step' => 2,
                'backgroundColor' => null,
                'title' => null,
                'description' => null,
                'paragraphs' => null,
                'icon' => null,
                'media' => asset('videos/onboarding/intro_app.MP4'),
            ],
            [
                'step' => 3,
                'backgroundColor' => '#FFE391',
                'title' => __('onboarding.steps.step_3.title'),
                'description' => __('onboarding.steps.step_3.description'),
                'paragraphs' => [
                    __('onboarding.steps.step_3.paragraphs.paragraph_1'),
                    __('onboarding.steps.step_3.paragraphs.paragraph_2'),
                    __('onboarding.steps.step_3.paragraphs.paragraph_3'),
                    __('onboarding.steps.step_3.paragraphs.paragraph_4'),
                ],
                'icon' => asset('images/half-moon.png'),
                'media' => null,
            ],
            [
                'step' => 4,
                'backgroundColor' => null,
                'title' => null,
                'description' => null,
                'paragraphs' => null,
                'icon' => null,
                'media' => asset('videos/onboarding/intro_app2.mp4'),
            ],
            [
                'step' => 5,
                'backgroundColor' => '#FFF6BD',
                'title' => __('onboarding.steps.step_5.title'),
                'description' => null,
                'paragraphs' => [
                    __('onboarding.steps.step_5.paragraphs.paragraph_1'),
                    __('onboarding.steps.step_5.paragraphs.paragraph_2'),
                    __('onboarding.steps.step_5.paragraphs.paragraph_3'),
                    __('onboarding.steps.step_5.paragraphs.paragraph_4'),
                ],
                'icon' => asset('images/book.png'),
                'media' => null,
            ],
        ];
    }
}
