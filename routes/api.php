<?php

use App\Http\Controllers\Api\Secretary\Auth\AuthController as SecretaryAuthController;
use App\Http\Controllers\Api\User\Auth\AuthController;
use App\Http\Controllers\Api\User\Auth\OAuthController;
use App\Http\Controllers\Api\User\GdprController;
use App\Http\Controllers\Api\User\Onboarding\OnboardingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/send-code', 'sendCode')->name('auth.sendCode');
    Route::post('/verify-code', 'verifyCode')->name('auth.verifyCode');
    Route::post('/login', 'login')->name('auth.login');
    Route::post('/register', 'register')->name('auth.register');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('auth.logout');
});

Route::post('oauth/signup/{provider}', [OAuthController::class, 'signup'])->name('oauth.signup');

Route::get('gdpr-consent', [GdprController::class, 'consent'])->name('gdpr.consent');
Route::put('gdpr/accept', [GdprController::class, 'accept'])->middleware('auth:sanctum')->name('gdpr.accept');

Route::controller(OnboardingController::class)->prefix('onboarding')->group(function () {
    Route::get('/', 'content')->name('onboarding.content');
    Route::put('/', 'update')->middleware('auth:sanctum')->name('onboarding.update');
});

Route::controller(SecretaryAuthController::class)->prefix('secretary')->group(function () {
    Route::post('/login', 'login')->name('secretary.login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('secretary.logout');
    Route::post('/password/forgot', 'forgotPassword')->name('secretary.password.forgot');
    Route::post('/password/reset', 'resetPassword')->name('secretary.password.reset');
});
