<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => request()->user(),
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $isOtherOrganization = $request->input('organization') === '__other__';
        $profile = $request->validate([
            'organization' => $isOtherOrganization
                ? ['required', 'in:__other__']
                : ['required', 'string', 'max:160', Rule::exists('organizations', 'name')],
            'other_organization' => $isOtherOrganization
                ? ['required', 'string', 'max:160']
                : ['nullable', 'prohibited'],
            'position' => ['required', 'string', 'max:120'],
        ]);

        if ($isOtherOrganization) {
            $profile['organization'] = $profile['other_organization'];
            unset($profile['other_organization']);
        }

        $request->user()->update($profile);

        return to_route('dashboard')->with('success', 'Your student organization profile is saved.');
    }
}
