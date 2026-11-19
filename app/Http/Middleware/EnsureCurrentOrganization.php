<?php

namespace App\Http\Middleware;

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

        $isMember = $request->user()
            ->organizations()
            ->whereKey($organizationId)
            ->exists();

        if (! $isMember) {
            $request->session()->forget('current_organization_id');

            return redirect()
                ->route('organizations.select')
                ->withErrors([
                    'organization_id' => 'Cette organisation n’est plus accessible.',
                ]);
        }

        return $next($request);
    }
}
