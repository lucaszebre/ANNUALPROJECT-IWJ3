<?php

namespace App\Http\Middleware;

use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every web request, before route model binding: sets the current organisation from
 * the session when the user is still a member of it, and locks a signed-in user without one
 * out of every organisation. It never redirects; `organization.selected` does that on the
 * routes that need an organisation.
 */
class ResolveCurrentOrganization
{
    public function __construct(private CurrentOrganization $organization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->session()->get('current_organization_id');
        $user = $request->user();

        if ($user && is_string($organizationId) && Str::isUuid($organizationId)
            && $user->organizations()->whereKey($organizationId)->exists()) {
            $this->organization->set($organizationId);
        } elseif ($user) {
            $this->organization->lock();
        }

        return $next($request);
    }

    /**
     * Code running after the response (and the next request of a test) starts with no organisation.
     */
    public function terminate(): void
    {
        $this->organization->forget();
    }
}
