<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSelectionController extends Controller
{
    public function postLogin(Request $request): RedirectResponse
    {
        $organizations = $request->user()
            ->organizations()
            ->orderBy('name')
            ->get();

        if ($organizations->isEmpty()) {
            abort(403, 'Votre compte n’est rattaché à aucune organisation.');
        }

        if ($organizations->count() === 1) {
            $request->session()->put(
                'current_organization_id',
                $organizations->first()->getKey()
            );

            return redirect()->intended(route('dashboard'));
        }

        return redirect()->route('organizations.select');
    }

    public function index(Request $request): View
    {
        return view('organizations.select', [
            'organizations' => $request->user()
                ->organizations()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid'],
        ]);

        $organization = $request->user()
            ->organizations()
            ->whereKey($validated['organization_id'])
            ->firstOrFail();

        $request->session()->put(
            'current_organization_id',
            $organization->getKey()
        );

        $request->session()->regenerateToken();

        return redirect()->intended(route('dashboard'));
    }
}
