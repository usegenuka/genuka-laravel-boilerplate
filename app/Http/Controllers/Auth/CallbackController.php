<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OAuthService $oauthService
    ) {}

    /**
     * Handle the OAuth callback from Genuka.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Validate required parameters
        $validated = $request->validate([
            'code' => 'required|string',
            'company_id' => 'required|string',
            'timestamp' => 'required|string',
            'hmac' => 'required|string',
            'redirect_to' => 'nullable|url',
        ]);

        try {
            // Process OAuth callback
            $company = $this->oauthService->handleCallback(
                code: $validated['code'],
                companyId: $validated['company_id'],
                timestamp: $validated['timestamp'],
                hmac: $validated['hmac']
            );

            Log::info('OAuth callback successful', [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);

            // Redirect to specified URL or default redirect
            $redirectUrl = $validated['redirect_to'] ?? config('genuka.default_redirect');

            return redirect($redirectUrl)->with('success', 'Successfully connected to Genuka!');
        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect back with error message
            return redirect(config('genuka.default_redirect'))
                ->with('error', 'Failed to connect to Genuka. Please try again.');
        }
    }
}
