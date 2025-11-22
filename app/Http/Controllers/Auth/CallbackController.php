<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OAuthService;
use App\Services\Session\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OAuthService $oauthService,
        protected SessionService $sessionService
    ) {}

    /**
     * Handle the OAuth callback from Genuka.
     *
     * IMPORTANT: According to Genuka OAuth guide:
     * - Use redirect_to value EXACTLY as received (URL-encoded) for HMAC verification
     * - Decode redirect_to ONLY for the actual HTTP redirect
     */
    public function __invoke(Request $request): RedirectResponse
    {

        // Validate required parameters
        $validated = $request->validate([
            'code' => 'required|string',
            'company_id' => 'required|string',
            'timestamp' => 'required|string',
            'hmac' => 'required|string',
            'redirect_to' => 'required|string',
        ]);
        // Get raw redirect_to parameter (URL-encoded as received)
        $redirectToEncoded = $request->query('redirect_to');

        try {
            // Process OAuth callback with URL-encoded redirect_to for HMAC
            $company = $this->oauthService->handleCallback(
                code: $validated['code'],
                companyId: $validated['company_id'],
                timestamp: $validated['timestamp'],
                hmac: $validated['hmac'],
                redirectTo: $redirectToEncoded // Use encoded value for HMAC
            );

            // Create JWT session (similar to Next.js createSession)
            $token = $this->sessionService->createSession($company->id);

            // Decode redirect_to ONLY for the actual HTTP redirect
            $redirectUrlDecoded = urldecode($validated['redirect_to']);

            // Add token as query parameter for frontend to store
            $redirectUrl = $redirectUrlDecoded.(parse_url($redirectUrlDecoded, PHP_URL_QUERY) ? '&' : '?').'token='.urlencode($token);

            return redirect($redirectUrl)
                ->with('success', 'Successfully connected to Genuka!');
        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
