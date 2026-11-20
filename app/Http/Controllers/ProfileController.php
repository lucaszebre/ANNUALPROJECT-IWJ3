<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Mon profil" page. The forms post to Fortify's routes
 * (user-profile-information.update and user-password.update).
 */
class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $memberships = $user->memberships()->with('organization')->get();

        // The organisation chosen after login, or the first one when none is chosen yet.
        $membership = $memberships->firstWhere('organization_id', $request->session()->get('current_organization_id'))
            ?? $memberships->first();

        return view('profile', [
            'user' => $user,
            'membership' => $membership,
            'canSwitchOrganization' => $memberships->count() > 1,
        ]);
    }
}
