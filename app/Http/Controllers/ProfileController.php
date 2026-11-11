<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => request()->user()]);
    }

    public function update(Request $request)
    {
        $profile = $request->validate([
            'organization' => ['required', 'string', 'max:160'],
            'position' => ['required', 'string', 'max:120'],
        ]);

        $request->user()->update($profile);

        return to_route('dashboard')->with('success', 'Your student organization profile is saved.');
    }
}
