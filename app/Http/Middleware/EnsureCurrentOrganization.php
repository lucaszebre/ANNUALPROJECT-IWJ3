<?php

namespace App\Http\Middleware;

use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `organization.selected`: the route reads an organisation's data, so one must be set.
 * ResolveCurrentOrganization has already checked that the user is a member of it.
 */
class EnsureCurrentOrganization
{
    public function __construct(private CurrentOrganization $organization) {}

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($this->organization->id() !== null) {
            return $next($request);
        }

        if (! $request->session()->has('current_organization_id')) {
            return redirect()->route('organizations.select');
        }

        // The organisation in the session is no longer one of the user's.
        $request->session()->forget('current_organization_id');

        return redirect()
            ->route('organizations.select')
            ->withErrors([
                'organization_id' => __('organizations.no_longer_accessible'),
            ]);
    }
}
