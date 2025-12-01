<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Genuka;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Session\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RefreshController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Refresh the session using the refresh cookie.
     * No request body needed - companyId comes from signed JWT cookie.
     */
    public function __invoke(): JsonResponse
    {
        // Get companyId from signed refresh cookie (tamper-proof)
        $companyId = $this->sessionService->verifyRefreshToken();

        if (!$companyId) {
            return response()->json([
                "error" => "Invalid or expired refresh token",
                "code" => "REFRESH_TOKEN_INVALID",
            ], 401);
        }

        // Get company from database
        $company = Company::find($companyId);

        if (!$company) {
            return response()->json([
                "error" => "Company not found",
                "code" => "COMPANY_NOT_FOUND",
            ], 404);
        }

        // Check if company has a refresh token
        if (!$company->refresh_token) {
            return response()->json([
                "error" => "No refresh token available. Please reinstall the app.",
                "code" => "NO_REFRESH_TOKEN",
            ], 401);
        }

        try {
            // Call Genuka API to refresh tokens
            $tokenData = Genuka::refreshAccessToken($company->refresh_token);

            // Update company with new tokens
            $company->update([
                "access_token" => $tokenData["access_token"],
                "refresh_token" => $tokenData["refresh_token"] ?? $company->refresh_token,
                "token_expires_at" => now()->addMinutes($tokenData["expires_in_minutes"] ?? 60),
            ]);

            // Create new session cookies
            $this->sessionService->createSession($companyId);

            return response()->json([
                "success" => true,
                "message" => "Session refreshed successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Session refresh failed", [
                "company_id" => $companyId,
                "error" => $e->getMessage(),
            ]);

            return response()->json([
                "error" => "Failed to refresh session. Please reinstall the app.",
                "code" => "REFRESH_FAILED",
            ], 401);
        }
    }
}
