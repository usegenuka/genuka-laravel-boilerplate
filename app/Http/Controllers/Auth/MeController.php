<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Session\SessionService;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SessionService $sessionService) {}

    /**
     * Get current authenticated company information.
     */
    public function __invoke(): JsonResponse
    {
        /**
         * @var Company $company
         */
        $company = $this->sessionService->getAuthenticatedCompany();

        if (!$company) {
            return response()->json(
                [
                    "error" => "Not authenticated",
                    "code" => "UNAUTHORIZED",
                ],
                401,
            );
        }

        return response()->json([
            "id" => $company->id,
            "handle" => $company->handle,
            "name" => $company->name,
            "description" => $company->description,
            "logo_url" => $company->logo_url,
            "phone" => $company->phone,
            "created_at" => $company->created_at,
            "updated_at" => $company->updated_at,
        ]);
    }
}
