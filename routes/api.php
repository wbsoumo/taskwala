<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Postback\PostbackController;
use Illuminate\Support\Facades\Route;

// 1. DEDICATED POSTBACK ENDPOINT (GET ONLY - Public S2S webhook authenticated per provider/campaign)
Route::get('/v1/postback/{provider_slug}', [PostbackController::class, 'handle'])->name('api.postback.handle');

// 2. MOBILE APP API V1 ROUTES
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user/profile', [AuthController::class, 'profile']);

        Route::get('/campaigns', [CampaignController::class, 'index']);
        Route::get('/links', [LinkController::class, 'index']);
        Route::post('/links/generate', [LinkController::class, 'generate']);

        Route::get('/wallet', [WalletController::class, 'index']);
        Route::post('/wallet/upi', [WalletController::class, 'updateUpi']);
        Route::post('/wallet/payout', [WalletController::class, 'requestPayout']);
        Route::get('/wallet/payout-requests', [WalletController::class, 'payoutRequests']);

        Route::get('/reports/clicks', [\App\Http\Controllers\Api\V1\ReportController::class, 'clicks']);
        Route::get('/reports/conversions', [\App\Http\Controllers\Api\V1\ReportController::class, 'conversions']);

        Route::get('/referrals/dashboard', [\App\Http\Controllers\Api\V1\ReferralController::class, 'dashboard']);
        Route::get('/referrals/team', [\App\Http\Controllers\Api\V1\ReferralController::class, 'team']);
        Route::get('/referrals/earnings', [\App\Http\Controllers\Api\V1\ReferralController::class, 'earnings']);
    });
});
