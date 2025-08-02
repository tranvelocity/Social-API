<?php

namespace Modules\Session\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\app\Http\Controllers\Controller;
use Modules\Poster\app\Services\PosterService;
use Modules\Session\App\Resources\MembershipResource;
use Modules\Session\App\Services\SessionService;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends Controller
{
    private SessionService $sessionService;
    private PosterService $posterService;

    /**
     * SessionController Constructor.
     */
    public function __construct(
        SessionService $sessionService,
        PosterService $posterService
    ) {
        $this->sessionService = $sessionService;
        $this->posterService = $posterService;
    }

    /**
     * Check whether the authenticated session belongs to a poster.
     *
     * This method checks whether the session corresponds to a user with the role of a poster.
     * It retrieves the User ID and the admin UUID from the authenticated session,
     * and then uses the PostService to determine if the user is a poster.
     *
     * @Route("/session/is-poster", methods={"POST"})
     * @return JsonResponse A JSON response indicating whether the session belongs to a poster.
     */
    public function isPoster(): JsonResponse
    {
        $userId = $this->getAuthorizedUserId();

        $adminUuid = $this->getAuthorizedAdminId();

        $isPoster = $this->posterService->isPoster($userId, $adminUuid);

        // Return response with HTTP status code 200
        return response()->json([
            'data' => [
                'is_poster' => $isPoster,
            ],
            'success' => true,
            'code' => Response::HTTP_OK,
        ]);
    }

    /**
     * Retrieve membership data for the authenticated session.
     *
     * This method retrieves membership data based on the provided role, User ID, and admin UUID.
     * It uses the SessionService to fetch membership data for the authenticated user.
     *
     * @Route("/session/membership", methods={"GET"})
     * @return MembershipResource A resource containing membership data for the authenticated user.
     */
    public function getMembership(): MembershipResource
    {
        $role = \request()->get('role');
        $userId = $this->getAuthorizedUserId();
        $adminUuid = $this->getAuthorizedAdminId();

        $membership = $this->sessionService->getMembership($adminUuid, $role, $userId);

        return new MembershipResource($membership);
    }
}
