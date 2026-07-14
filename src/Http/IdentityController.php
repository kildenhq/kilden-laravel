<?php

declare(strict_types=1);

namespace Kilden\Laravel\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kilden\Laravel\KildenManager;
use Kilden\Laravel\KildenRoutes;

/**
 * POST /kilden/identity — the endpoint the Kilden web SDK polls to refresh
 * its identity token (60s before expiry and on 401). Signs for the
 * authenticated user only; the auth middleware in front is what makes the
 * token trustworthy.
 */
class IdentityController
{
    public function __invoke(Request $request, KildenManager $kilden): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            // Unreachable behind auth middleware; defense in depth.
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $distinctId = (string) $user->getAuthIdentifier();
        $traits = KildenRoutes::traitsFor($user);

        return new JsonResponse([
            'distinct_id' => $distinctId,
            'token' => $kilden->identityToken($distinctId, $traits),
            'traits' => $traits === [] ? new \stdClass() : $traits,
        ]);
    }
}
