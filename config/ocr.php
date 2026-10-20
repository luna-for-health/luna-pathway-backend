<?php

return [

    'provider' => env('OCR_PROVIDER', 'mistral'),

    'mistral' => [
        'api_key' => env('OCR_MISTRAL_API_KEY', env('MISTRAL_API_KEY')),
        'model' => env('OCR_MISTRAL_MODEL', 'mistral-ocr-latest'),
        'endpoint' => env('OCR_MISTRAL_ENDPOINT', 'https://api.mistral.ai/v1/ocr'),
        'timeout' => (int) env('OCR_MISTRAL_TIMEOUT', 120),
        'summary_model' => env('OCR_MISTRAL_SUMMARY_MODEL', 'mistral-small-latest'),
        'summary_endpoint' => env('OCR_MISTRAL_SUMMARY_ENDPOINT', 'https://api.mistral.ai/v1/chat/completions'),
    ],

];
