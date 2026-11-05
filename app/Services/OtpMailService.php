<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpMailService
{
    /**
     * Send email verification code for new user registrations.
     */
    public static function sendRegistrationVerificationOtp(string $email, string $name, string $otp): bool
    {
        try {
            Mail::send('emails.otp', [
                'name' => $name,
                'otp' => $otp,
                'heading' => 'Verify Your Email Address',
                'subject' => 'Verify Your Taskwala Account - Verification Code: ' . $otp,
                'bodyText' => 'Welcome to Taskwala! Please verify your email address to activate your partner account and start generating affiliate links.',
            ], function ($message) use ($email, $otp) {
                $message->to($email)
                    ->subject('Your Taskwala Email Verification Code: ' . $otp);
            });

            Log::info("Email verification OTP sent to {$email}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send registration OTP email to {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset code to user's registered email.
     */
    public static function sendPasswordResetOtp(string $email, string $name, string $otp): bool
    {
        try {
            Mail::send('emails.otp', [
                'name' => $name,
                'otp' => $otp,
                'heading' => 'Reset Your Password',
                'subject' => 'Taskwala Password Reset Code: ' . $otp,
                'bodyText' => 'We received a request to reset your Taskwala account password. Use the verification code below to set a new password:',
            ], function ($message) use ($email, $otp) {
                $message->to($email)
                    ->subject('Taskwala Password Reset Code: ' . $otp);
            });

            Log::info("Password reset OTP sent to {$email}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send password reset OTP email to {$email}: " . $e->getMessage());
            return false;
        }
    }
}
