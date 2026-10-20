<?php

namespace App\Http\Resources\Gdpr;

use Illuminate\Http\Resources\Json\JsonResource;

class GdprConsentResource extends JsonResource
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
                'title' => __('gdpr-consent.step_1.title'),
                'backgroundColor' => '#FFEDCC',
                'paragraphs' => [
                    __('gdpr-consent.step_1.paragraphs.paragraph_1'),
                    __('gdpr-consent.step_1.paragraphs.paragraph_2'),
                    __('gdpr-consent.step_1.paragraphs.paragraph_3'),
                ],
                'icon' => asset('images/gdpr-consent/user.png'),
                'media' => null,
                'media_text' => null,
                'link' => null,
            ],
            [
                'step' => 2,
                'title' => __('gdpr-consent.step_2.title'),
                'backgroundColor' => '#D4CCFF',
                'paragraphs' => [
                    __('gdpr-consent.step_2.paragraphs.paragraph_1'),
                    __('gdpr-consent.step_2.paragraphs.paragraph_2'),
                ],
                'icon' => asset('images/gdpr-consent/protected.png'),
                'media' => null,
                'media_text' => null,
                'link' => __('gdpr-consent.step_2.link_text'),
            ],
            [
                'step' => 3,
                'title' => __('gdpr-consent.step_3.title'),
                'backgroundColor' => '#FFE391',
                'paragraphs' => [
                    __('gdpr-consent.step_3.paragraphs.paragraph_1'),
                    __('gdpr-consent.step_3.paragraphs.paragraph_2'),
                    __('gdpr-consent.step_3.paragraphs.paragraph_3'),
                ],
                'icon' => asset('images/gdpr-consent/medical.png'),
                'media' => null,
                'media_text' => null,
                'link' => null,
            ],
            [
                'step' => 4,
                'title' => null,
                'backgroundColor' => null,
                'paragraphs' => null,
                'icon' => null,
                'media' => asset('videos/gdpr-consent/vid1.mp4'),
                'media_text' => __('gdpr-consent.step_4.media_text'),
                'link' => null,
            ],
        ];
    }
}
