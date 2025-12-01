<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Session\SessionService;
use Illuminate\Http\JsonResponse;

class CheckController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Check if user is authenticated.
     */
    public function __invoke(): JsonResponse
    {
        $isAuthenticated = $this->sessionService->isAuthenticated();

        return response()->json([
            "authenticated" => $isAuthenticated,
        ]);
    }
}
