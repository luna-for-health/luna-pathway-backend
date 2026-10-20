<?php

namespace App\Services\Ocr;

class OcrServiceFactory
{
    public static function make(): OcrServiceInterface
    {
        return match (config('ocr.provider', 'mistral')) {
            default => app(MistralOcrService::class),
        };
    }
}
