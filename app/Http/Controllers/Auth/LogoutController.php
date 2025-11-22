<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Session\SessionService;
use Illuminate\Http\RedirectResponse;

class LogoutController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Logout the user by destroying the session.
     */
    public function __invoke(): RedirectResponse
    {
        $this->sessionService->destroySession();

        return redirect('/')->with('success', 'Successfully logged out from Genuka.');
    }
}
