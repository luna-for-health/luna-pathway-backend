<?php

use App\Http\Controllers\Api\User\Auth\AuthController;
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
