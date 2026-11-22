<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $organizationId = $request->session()->get('current_organization_id');

        if (! $organizationId) {
            return redirect()->route('organizations.select');
        }

        $organization = Organization::query()
            ->whereKey($organizationId)
            ->whereHas('memberships', fn ($membership) => $membership
                ->where('user_id', $request->user()->getKey()))
            ->first();

        if (! $organization instanceof Organization) {
            $request->session()->forget('current_organization_id');

            return redirect()
                ->route('organizations.select')
                ->withErrors([
                    'organization_id' => __('organizations.no_longer_accessible'),
                ]);
        }

        $request->attributes->set('current_organization', $organization);

        return $next($request);
    }
}
